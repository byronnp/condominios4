<?php

namespace App\Http\Controllers\Api\Users;

use App\Domain\Users\Services\UserInvitationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Users\ResendInvitationRequest;
use App\Models\Condominium;
use App\Models\Role;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ResendInvitationController extends Controller
{
    #[OA\Post(path: '/api/users/{user}/resend-invitation', operationId: 'usersResendInvitation', summary: 'Reenviar invitación de acceso', security: [['bearerAuth' => []]], tags: ['Invitaciones'], parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(example: ['condominium_id' => 1])), responses: [new OA\Response(response: 200, description: 'Invitación reenviada'), new OA\Response(response: 403, description: 'No autorizado'), new OA\Response(response: 422, description: 'Usuario activo o asignación inválida')])]
    public function __invoke(ResendInvitationRequest $request, User $user, UserInvitationService $service): JsonResponse
    {
        abort_if($user->is_access_enabled, 422, 'El usuario ya tiene acceso habilitado.');

        $actor = $request->user();
        $query = $user->condominiums()->wherePivotNull('deleted_at');
        if ($request->validated('condominium_id')) {
            $query->whereKey($request->validated('condominium_id'));
        }
        $condominium = $query->first();
        abort_if(! $condominium, 422, 'El usuario no está asignado al condominio.');

        $authorized = $actor->isPlatformAdmin()
            || $actor->hasPermission('users.invite', $condominium)
            || $actor->hasPermission('users.resend_invitation', $condominium);
        abort_unless($authorized, 403, 'No tienes permiso para reenviar invitaciones.');

        $role = Role::query()->where('condominium_id', $condominium->id)->where('code', 'administrador')->first();
        abort_if(! $role, 422, 'No existe el rol administrador para el condominio.');

        $service->invite($user, $condominium, $role, $actor);

        return ApiResponse::success(message: 'Invitación reenviada correctamente.');
    }
}
