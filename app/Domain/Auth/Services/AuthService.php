<?php

namespace App\Domain\Auth\Services;

use App\Models\AuthSession;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class AuthService
{
    public function __construct(
        private readonly JwtTokenService $jwtTokenService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function register(array $data, Request $request): array
    {
        return DB::transaction(function () use ($data, $request): array {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'country' => $data['country'],
                'document_type_id' => $data['document_type_id'],
                'document_number' => $data['document_number'],
            ]);

            return $this->createSessionTokens($user, $request);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function login(array $data, Request $request): array
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw new RuntimeException('Credenciales inválidas.');
        }

        return DB::transaction(fn (): array => $this->createSessionTokens($user, $request));
    }

    /**
     * @return array<string, mixed>
     */
    public function refresh(string $refreshToken, Request $request): array
    {
        return DB::transaction(function () use ($refreshToken, $request): array {
            $storedToken = RefreshToken::query()
                ->valid()
                ->where('token_hash', $this->hashRefreshToken($refreshToken))
                ->with(['user', 'authSession'])
                ->lockForUpdate()
                ->first();

            if (! $storedToken || ! $storedToken->authSession->is_active) {
                throw new RuntimeException('Refresh token inválido o expirado.');
            }

            $storedToken->update([
                'revoked_at' => now(),
                'revoked_by_user_id' => $storedToken->user_id,
                'revoke_reason' => 'rotated',
            ]);

            $storedToken->authSession->update([
                'last_activity_at' => now(),
            ]);

            return $this->issueTokens($storedToken->user, $storedToken->authSession, $request);
        });
    }

    public function logout(User $user, AuthSession $session, ?string $refreshToken = null): void
    {
        DB::transaction(function () use ($user, $session, $refreshToken): void {
            $session->update([
                'ended_at' => now(),
                'last_activity_at' => now(),
                'logout_reason' => 'logout',
                'is_active' => false,
            ]);

            $query = RefreshToken::query()
                ->where('user_id', $user->id)
                ->where('auth_session_id', $session->id)
                ->whereNull('revoked_at');

            if ($refreshToken !== null) {
                $query->where('token_hash', $this->hashRefreshToken($refreshToken));
            }

            $query->update([
                'revoked_at' => now(),
                'revoked_by_user_id' => $user->id,
                'revoke_reason' => 'logout',
            ]);
        });
    }

    public function logoutAll(User $user): void
    {
        DB::transaction(function () use ($user): void {
            AuthSession::query()
                ->where('user_id', $user->id)
                ->active()
                ->update([
                    'ended_at' => now(),
                    'last_activity_at' => now(),
                    'logout_reason' => 'logout_all',
                    'is_active' => false,
                ]);

            RefreshToken::query()
                ->where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => now(),
                    'revoked_by_user_id' => $user->id,
                    'revoke_reason' => 'logout_all',
                ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function createSessionTokens(User $user, Request $request): array
    {
        $session = AuthSession::create([
            'user_id' => $user->id,
            'started_at' => now(),
            'last_activity_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_name' => $request->string('device_name')->toString() ?: null,
            'login_method' => 'password',
            'is_active' => true,
        ]);

        return $this->issueTokens($user, $session, $request);
    }

    /**
     * @return array<string, mixed>
     */
    private function issueTokens(User $user, AuthSession $session, Request $request): array
    {
        $access = $this->jwtTokenService->createAccessToken($user, $session);
        $plainRefreshToken = Str::random(80);
        $refreshExpiresAt = now()->addDays(config('jwt.refresh_ttl_days'));

        $refreshToken = RefreshToken::create([
            'user_id' => $user->id,
            'auth_session_id' => $session->id,
            'token_hash' => $this->hashRefreshToken($plainRefreshToken),
            'expires_at' => $refreshExpiresAt,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return [
            'user' => $user->fresh('documentType'),
            'auth_session' => $session->fresh(),
            'access_token' => $access['token'],
            'refresh_token' => $plainRefreshToken,
            'refresh_token_model' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => (int) now()->diffInSeconds($access['expires_at']),
            'access_token_expires_at' => $access['expires_at'],
            'refresh_token_expires_at' => $refreshExpiresAt,
        ];
    }

    private function hashRefreshToken(string $refreshToken): string
    {
        return hash('sha256', $refreshToken);
    }
}
