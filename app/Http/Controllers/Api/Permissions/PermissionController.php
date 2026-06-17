<?php

namespace App\Http\Controllers\Api\Permissions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Permissions\PermissionStoreRequest;
use App\Http\Resources\Api\Permissions\PermissionResource;
use App\Models\Permission;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PermissionController extends Controller
{
    #[OA\Get(path: '/api/permissions', operationId: 'permissionsIndex', summary: 'Listar permisos', tags: ['Permisos'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Permisos encontrados')])]
    public function index(): JsonResponse
    {
        return ApiResponse::success(
            PermissionResource::collection(Permission::query()->orderBy('module')->orderBy('action')->get()),
            'Permisos encontrados.'
        );
    }

    #[OA\Post(path: '/api/permissions', operationId: 'permissionsStore', summary: 'Crear permiso', tags: ['Permisos'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Permiso creado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function store(PermissionStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        $permission = Permission::create([
            ...$data,
            'is_system' => false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success(new PermissionResource($permission), 'Permiso creado correctamente.', 201);
    }
}
