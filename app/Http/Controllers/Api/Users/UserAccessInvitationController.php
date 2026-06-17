<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Users\UserAccessInvitationAcceptRequest;
use App\Http\Requests\Api\Users\UserAccessInvitationCancelRequest;
use App\Http\Requests\Api\Users\UserAccessInvitationStoreRequest;
use App\Http\Resources\Api\Users\UserAccessInvitationResource;
use App\Models\Condominium;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserAccessInvitation;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class UserAccessInvitationController extends Controller
{
    #[OA\Post(path: '/api/condominiums/{condominium}/units/{unit}/users/{user}/access-invitations', operationId: 'accessInvitationsStore', summary: 'Crear invitación de acceso', tags: ['Invitaciones'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Invitación creada')])]
    public function store(UserAccessInvitationStoreRequest $request, Condominium $condominium, Unit $unit, User $user): JsonResponse
    {
        $this->assertUnitUser($condominium, $unit, $user);

        $data = $request->validated();

        abort_if($user->is_access_enabled, 422, 'El usuario ya tiene acceso habilitado.');

        $token = Str::random(64);
        $invitation = UserAccessInvitation::create([
            'condominium_id' => $condominium->id,
            'unit_id' => $unit->id,
            'user_id' => $user->id,
            'invited_by_user_id' => $request->user()->id,
            'email' => $data['email'],
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(7),
        ]);

        return ApiResponse::success(new UserAccessInvitationResource([
            'id' => $invitation->id,
            'user_id' => $user->id,
            'email' => $invitation->email,
            'token' => $token,
            'accept_url' => url("/api/access-invitations/{$token}/accept"),
            'expires_at' => $invitation->expires_at->toISOString(),
        ]), 'Invitación de acceso creada correctamente.', 201);
    }

    #[OA\Post(path: '/api/access-invitations/{token}/accept', operationId: 'accessInvitationsAccept', summary: 'Aceptar invitación de acceso', tags: ['Invitaciones'], responses: [new OA\Response(response: 200, description: 'Invitación aceptada')])]
    public function accept(UserAccessInvitationAcceptRequest $request, string $token): JsonResponse
    {
        $data = $request->validated();

        $invitation = UserAccessInvitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->whereNull('cancelled_at')
            ->first();

        if (! $invitation || $invitation->expires_at->isPast()) {
            return ApiResponse::error('Invitación inválida o expirada.', 422, code: 'access_invitation_invalid');
        }

        DB::transaction(function () use ($invitation, $data): void {
            $invitation->user->update([
                'email' => $invitation->email,
                'password' => $data['password'],
                'is_access_enabled' => true,
            ]);

            $invitation->update(['accepted_at' => now()]);
        });

        return ApiResponse::success(message: 'Invitación aceptada correctamente.');
    }

    public function cancel(UserAccessInvitationCancelRequest $request, Condominium $condominium, Unit $unit, UserAccessInvitation $invitation): JsonResponse
    {
        abort_if($invitation->condominium_id !== $condominium->id || $invitation->unit_id !== $unit->id, 404);

        $data = $request->validated();

        $invitation->update([
            'cancelled_at' => now(),
            'cancelled_by_user_id' => $request->user()->id,
            'cancel_reason' => $data['cancel_reason'] ?? null,
        ]);

        return ApiResponse::success(message: 'Invitación cancelada correctamente.');
    }

    private function assertUnitUser(Condominium $condominium, Unit $unit, User $user): void
    {
        abort_if($unit->condominium_id !== $condominium->id, 404);

        $exists = DB::table('unit_user')
            ->where('unit_id', $unit->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->exists();

        abort_if(! $exists, 422, 'El usuario no tiene una relación activa con la unidad.');
    }
}
