<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Users\UserBillingProfileStoreRequest;
use App\Http\Resources\Api\Users\UserBillingProfileResource;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class UserBillingProfileController extends Controller
{
    #[OA\Get(path: '/api/users/{user}/billing-profiles', operationId: 'userBillingProfilesIndex', summary: 'Listar perfiles de facturación', tags: ['Facturación usuarios'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Perfiles encontrados')])]
    public function index(User $user): JsonResponse
    {
        return ApiResponse::success(
            UserBillingProfileResource::collection($user->billingProfiles()->with('documentType')->get()),
            'Perfiles de facturación encontrados.'
        );
    }

    #[OA\Post(path: '/api/users/{user}/billing-profiles', operationId: 'userBillingProfilesStore', summary: 'Crear perfil de facturación', tags: ['Facturación usuarios'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Perfil creado')])]
    public function store(UserBillingProfileStoreRequest $request, User $user): JsonResponse
    {
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
}
