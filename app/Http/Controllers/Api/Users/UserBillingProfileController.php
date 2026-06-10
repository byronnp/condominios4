<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\ValidCatalogItem;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class UserBillingProfileController extends Controller
{
    #[OA\Get(path: '/api/users/{user}/billing-profiles', operationId: 'userBillingProfilesIndex', summary: 'Listar perfiles de facturación', tags: ['Facturación usuarios'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Perfiles encontrados')])]
    public function index(User $user): JsonResponse
    {
        return ApiResponse::success(
            $user->billingProfiles()->with('documentType')->get(),
            'Perfiles de facturación encontrados.'
        );
    }

    #[OA\Post(path: '/api/users/{user}/billing-profiles', operationId: 'userBillingProfilesStore', summary: 'Crear perfil de facturación', tags: ['Facturación usuarios'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Perfil creado')])]
    public function store(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'document_type_id' => ['required', 'integer', new ValidCatalogItem('document_types')],
            'document_number' => ['required', 'string', 'max:30'],
            'business_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'billing_email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:2'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (($data['is_default'] ?? false) === true) {
            $user->billingProfiles()->update(['is_default' => false]);
        }

        $profile = $user->billingProfiles()->create([
            ...$data,
            'country' => $data['country'] ?? 'EC',
            'is_default' => $data['is_default'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success($profile->load('documentType'), 'Perfil de facturación creado correctamente.', 201);
    }
}
