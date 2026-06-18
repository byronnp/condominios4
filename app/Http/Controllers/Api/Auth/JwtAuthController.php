<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\Auth\Services\AuthService;
use App\Domain\Auth\Services\JwtTokenService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\LogoutRequest;
use App\Http\Requests\Api\Auth\RefreshTokenRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\Api\Auth\AuthSessionResource;
use App\Http\Resources\Api\Auth\UserResource;
use App\Http\Resources\Api\Condominiums\CondominiumResource;
use App\Http\Resources\Api\Roles\RoleResource;
use App\Models\AuthSession;
use App\Models\Condominium;
use App\Models\Role;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use OpenApi\Attributes as OA;
use RuntimeException;

class JwtAuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly JwtTokenService $jwtTokenService,
    ) {}

    #[OA\Post(path: '/api/auth/register', operationId: 'authRegister', summary: 'Registrar usuario', tags: ['Autenticación'], responses: [new OA\Response(response: 201, description: 'Usuario registrado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function register(RegisterRequest $request): JsonResponse
    {
        $tokens = $this->authService->register($request->validated(), $request);

        return ApiResponse::success(
            data: $this->tokenResponse($tokens),
            message: 'Usuario registrado correctamente.',
            status: 201,
        );
    }

    #[OA\Post(path: '/api/auth/login', operationId: 'authLogin', summary: 'Iniciar sesión', tags: ['Autenticación'], responses: [new OA\Response(response: 200, description: 'Sesión iniciada'), new OA\Response(response: 401, description: 'Credenciales inválidas')])]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $tokens = $this->authService->login($request->validated(), $request);
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 401, code: 'invalid_credentials');
        }

        return ApiResponse::success(
            data: $this->tokenResponse($tokens),
            message: 'Sesión iniciada correctamente.',
        );
    }

    #[OA\Post(path: '/api/auth/refresh', operationId: 'authRefresh', summary: 'Renovar access token', tags: ['Autenticación'], responses: [new OA\Response(response: 200, description: 'Token renovado'), new OA\Response(response: 401, description: 'Refresh token inválido')])]
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $tokens = $this->authService->refresh($request->validated('refresh_token'), $request);
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 401, code: 'refresh_token_invalid');
        }

        return ApiResponse::success(
            data: $this->tokenResponse($tokens),
            message: 'Token renovado correctamente.',
        );
    }

    #[OA\Post(path: '/api/auth/logout', operationId: 'authLogout', summary: 'Cerrar sesión actual', tags: ['Autenticación'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Sesión cerrada'), new OA\Response(response: 401, description: 'No autenticado')])]
    public function logout(LogoutRequest $request): JsonResponse
    {
        $user = $request->user();
        $session = $request->attributes->get('auth_session');
        $payload = $request->attributes->get('jwt_payload');

        $this->authService->logout($user, $session, $request->validated('refresh_token'));
        $this->jwtTokenService->revokeAccessToken($payload, $user, 'logout');

        return ApiResponse::success(message: 'Sesión cerrada correctamente.');
    }

    #[OA\Post(path: '/api/auth/logout-all', operationId: 'authLogoutAll', summary: 'Cerrar todas las sesiones', tags: ['Autenticación'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Sesiones cerradas')])]
    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());
        $this->jwtTokenService->revokeAccessToken($request->attributes->get('jwt_payload'), $request->user(), 'logout_all');

        return ApiResponse::success(message: 'Todas las sesiones fueron cerradas correctamente.');
    }

    #[OA\Get(path: '/api/auth/me', operationId: 'authMe', summary: 'Obtener usuario autenticado', tags: ['Autenticación'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Usuario autenticado')])]
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $condominium = $this->currentCondominium($request, $user);

        return ApiResponse::success(
            data: [
                'user' => new UserResource($user->load('documentType')),
                'platform_role' => $this->platformRole($user),
                'is_platform_admin' => $user->isPlatformAdmin(),
                'condominium' => $condominium ? new CondominiumResource($condominium) : null,
                'roles' => RoleResource::collection($condominium ? $this->rolesForCondominium($user, $condominium) : collect()),
                'permissions' => $condominium
                    ? $user->permissionsForCondominium($condominium)->pluck('code')->values()
                    : collect(),
                'auth_session' => new AuthSessionResource($request->attributes->get('auth_session')),
            ],
            message: 'Usuario autenticado.',
        );
    }

    #[OA\Get(path: '/api/auth/sessions', operationId: 'authSessions', summary: 'Listar sesiones activas', tags: ['Autenticación'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Sesiones encontradas')])]
    public function sessions(Request $request): JsonResponse
    {
        $sessions = AuthSession::query()
            ->where('user_id', $request->user()->id)
            ->latest('last_activity_at')
            ->get();

        return ApiResponse::success(
            data: AuthSessionResource::collection($sessions),
            message: 'Sesiones encontradas.',
        );
    }

    #[OA\Delete(path: '/api/auth/sessions/{session}', operationId: 'authRevokeSession', summary: 'Revocar una sesión', tags: ['Autenticación'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Sesión revocada'), new OA\Response(response: 404, description: 'Sesión no encontrada')])]
    public function revokeSession(Request $request, AuthSession $session): JsonResponse
    {
        abort_if($session->user_id !== $request->user()->id, 404);

        $session->update([
            'ended_at' => now(),
            'last_activity_at' => now(),
            'logout_reason' => 'revoked',
            'is_active' => false,
        ]);

        $session->refreshTokens()
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_by_user_id' => $request->user()->id,
                'revoke_reason' => 'session_revoked',
            ]);

        return ApiResponse::success(message: 'Sesión revocada correctamente.');
    }

    /**
     * @param  array<string, mixed>  $tokens
     * @return array<string, mixed>
     */
    private function tokenResponse(array $tokens): array
    {
        return [
            'token_type' => $tokens['token_type'],
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $tokens['expires_in'],
            'access_token_expires_at' => $tokens['access_token_expires_at']->toISOString(),
            'refresh_token_expires_at' => $tokens['refresh_token_expires_at']->toISOString(),
        ];
    }

    private function currentCondominium(Request $request, User $user): ?Condominium
    {
        $headerCondominiumId = $request->header('X-Condominium-Id');

        if ($headerCondominiumId) {
            return $user->condominiums()
                ->where('condominiums.id', $headerCondominiumId)
                ->wherePivot('is_active', true)
                ->wherePivotNull('deleted_at')
                ->first();
        }

        return $user->condominiums()
            ->wherePivot('is_active', true)
            ->wherePivotNull('deleted_at')
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function platformRole(User $user): ?array
    {
        $role = $user->platformRole();

        if (! $role) {
            return null;
        }

        return [
            'id' => $role->id,
            'name' => $role->name === 'admin' ? 'Administrador Senior' : $role->name,
            'code' => $role->code ?: 'administrador_senior',
        ];
    }

    /**
     * @return Collection<int, Role>
     */
    private function rolesForCondominium(User $user, Condominium $condominium): Collection
    {
        $condominiumUser = $user->condominiums()
            ->where('condominiums.id', $condominium->id)
            ->wherePivot('is_active', true)
            ->wherePivotNull('deleted_at')
            ->first()?->pivot;

        if (! $condominiumUser) {
            return collect();
        }

        return Role::query()
            ->select('roles.*')
            ->join('condominium_user_role', 'condominium_user_role.role_id', '=', 'roles.id')
            ->where('condominium_user_role.condominium_user_id', $condominiumUser->id)
            ->where('roles.condominium_id', $condominium->id)
            ->where('roles.is_active', true)
            ->whereNull('roles.deleted_at')
            ->whereNull('condominium_user_role.deleted_at')
            ->orderBy('roles.name')
            ->get();
    }
}
