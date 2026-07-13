<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Users\UserBillingProfileStoreRequest;
use App\Http\Resources\Api\Users\UserBillingProfileResource;
use App\Models\Condominium;
use App\Models\Unit;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class UserBillingProfileController extends Controller
{
    #[OA\Get(path: '/api/condominiums/{condominium}/units/{unit}/users/{user}/billing-profiles', operationId: 'unitUserBillingProfilesIndex', summary: 'Listar perfiles de facturación del usuario en la vivienda', tags: ['Facturación usuarios'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Perfiles encontrados'), new OA\Response(response: 404, description: 'Recurso fuera del condominio o vivienda')])]
    public function index(Condominium $condominium, Unit $unit, User $user): JsonResponse
    {
        $this->assertUnitUserContext($condominium, $unit, $user);

        return ApiResponse::success(
            UserBillingProfileResource::collection($user->billingProfiles()->with('documentType')->get()),
            'Perfiles de facturación encontrados.'
        );
    }

    #[OA\Post(path: '/api/condominiums/{condominium}/units/{unit}/users/{user}/billing-profiles', operationId: 'unitUserBillingProfilesStore', summary: 'Crear perfil de facturación del usuario en la vivienda', tags: ['Facturación usuarios'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Perfil creado'), new OA\Response(response: 404, description: 'Recurso fuera del condominio o vivienda')])]
    public function store(UserBillingProfileStoreRequest $request, Condominium $condominium, Unit $unit, User $user): JsonResponse
    {
        $this->assertUnitUserContext($condominium, $unit, $user);

        $data = $request->validated();

        if (($data['is_default'] ?? false) === true) {
            $user->billingProfiles()->update(['is_default' => false]);
        }

        $profile = $user->billingProfiles()->create([
            ...$data,
            'country' => $data['country'] ?? 'EC',
            'is_default' => $data['is_default'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success(new UserBillingProfileResource($profile->load('documentType')), 'Perfil de facturación creado correctamente.', 201);
    }

    private function assertUnitUserContext(Condominium $condominium, Unit $unit, User $user): void
    {
        abort_if($unit->condominium_id !== $condominium->id, 404);
        abort_if(! $unit->users()
            ->where('users.id', $user->id)
            ->wherePivotNull('deleted_at')
            ->exists(), 404);
    }
}
