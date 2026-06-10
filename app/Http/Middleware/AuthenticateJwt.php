<?php

namespace App\Http\Middleware;

use App\Domain\Auth\Services\JwtTokenService;
use App\Models\AuthSession;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Closure;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AuthenticateJwt
{
    public function __construct(
        private readonly JwtTokenService $jwtTokenService,
    ) {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $token = $request->bearerToken();

        if (! $token) {
            return ApiResponse::error('Token de acceso requerido.', 401, code: 'access_token_required');
        }

        try {
            $payload = $this->jwtTokenService->decode($token);
        } catch (ExpiredException) {
            return ApiResponse::error('Token de acceso expirado.', 401, code: 'access_token_expired');
        } catch (Throwable) {
            return ApiResponse::error('Token de acceso inválido.', 401, code: 'access_token_invalid');
        }

        if (($payload->type ?? null) !== 'access' || ! isset($payload->sub, $payload->jti, $payload->auth_session_id)) {
            return ApiResponse::error('Token de acceso inválido.', 401, code: 'access_token_invalid');
        }

        if ($this->jwtTokenService->isRevoked($payload->jti)) {
            return ApiResponse::error('Token de acceso revocado.', 401, code: 'access_token_revoked');
        }

        $user = User::find((int) $payload->sub);
        $session = AuthSession::query()
            ->active()
            ->whereKey((int) $payload->auth_session_id)
            ->where('user_id', $user?->id)
            ->first();

        if (! $user || ! $session) {
            return ApiResponse::error('Sesión inválida o expirada.', 401, code: 'session_invalid');
        }

        $session->update(['last_activity_at' => now()]);

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('jwt_payload', $payload);
        $request->attributes->set('auth_session', $session);

        return $next($request);
    }
}
