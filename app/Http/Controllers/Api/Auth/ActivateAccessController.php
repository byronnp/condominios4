<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\Users\Services\UserInvitationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ActivateAccessRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use RuntimeException;

class ActivateAccessController extends Controller
{
    #[OA\Post(path: '/api/auth/activate-access', operationId: 'authActivateAccess', summary: 'Activar acceso mediante invitación', tags: ['Autenticación'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['token', 'password', 'password_confirmation'], example: ['token' => 'token-de-64-caracteres', 'password' => 'ClaveSegura123!', 'password_confirmation' => 'ClaveSegura123!'])), responses: [new OA\Response(response: 200, description: 'Acceso activado'), new OA\Response(response: 422, description: 'Invitación inválida o expirada')])]
    public function __invoke(ActivateAccessRequest $request, UserInvitationService $service): JsonResponse
    {
        try {
            $service->accept($request->validated());
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422, code: 'access_invitation_invalid');
        }

        return ApiResponse::success(message: 'Acceso activado correctamente. Ya puedes iniciar sesión.');
    }
}
