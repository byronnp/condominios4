<?php

namespace App\Domain\Auth\Services;

use App\Models\AuthSession;
use App\Models\RevokedToken;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use stdClass;

class JwtTokenService
{
    /**
     * @return array{token: string, jti: string, expires_at: Carbon}
     */
    public function createAccessToken(User $user, AuthSession $session): array
    {
        $issuedAt = now();
        $expiresAt = $issuedAt->copy()->addMinutes(config('jwt.access_ttl_minutes'));
        $jti = (string) Str::uuid();

        $payload = [
            'iss' => config('jwt.issuer'),
            'sub' => (string) $user->id,
            'jti' => $jti,
            'type' => 'access',
            'auth_session_id' => $session->id,
            'iat' => $issuedAt->timestamp,
            'exp' => $expiresAt->timestamp,
        ];

        return [
            'token' => JWT::encode($payload, $this->secret(), config('jwt.algorithm')),
            'jti' => $jti,
            'expires_at' => $expiresAt,
        ];
    }

    public function decode(string $token): stdClass
    {
        return JWT::decode($token, new Key($this->secret(), config('jwt.algorithm')));
    }

    public function revokeAccessToken(stdClass $payload, ?User $revokedBy = null, string $reason = 'logout'): void
    {
        if (! isset($payload->jti, $payload->exp)) {
            return;
        }

        RevokedToken::firstOrCreate([
            'jti' => $payload->jti,
        ], [
            'user_id' => isset($payload->sub) ? (int) $payload->sub : null,
            'auth_session_id' => isset($payload->auth_session_id) ? (int) $payload->auth_session_id : null,
            'expires_at' => Carbon::createFromTimestamp((int) $payload->exp),
            'revoked_at' => now(),
            'revoked_by_user_id' => $revokedBy?->id,
            'reason' => $reason,
        ]);
    }

    public function isRevoked(string $jti): bool
    {
        return RevokedToken::query()
            ->where('jti', $jti)
            ->where('expires_at', '>', now())
            ->exists();
    }

    private function secret(): string
    {
        $secret = (string) config('jwt.secret');

        if ($secret === '') {
            throw new RuntimeException('JWT secret is not configured.');
        }

        return $secret;
    }
}
