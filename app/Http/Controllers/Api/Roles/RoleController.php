<?php

namespace App\Http\Controllers\Api\Roles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Roles\RoleStoreRequest;
use App\Http\Requests\Api\Roles\RoleSyncPermissionsRequest;
use App\Http\Resources\Api\Roles\RoleResource;
use App\Models\Condominium;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class RoleController extends Controller
{
    #[OA\Get(path: '/api/condominiums/{condominium}/roles', operationId: 'rolesIndex', summary: 'Listar roles por condominio', tags: ['Roles'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Roles encontrados')])]
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            RoleResource::collection($condominium->roles()->with('permissions')->orderBy('name')->get()),
            'Roles encontrados.'
        );
    }

    #[OA\Post(path: '/api/condominiums/{condominium}/roles', operationId: 'rolesStore', summary: 'Crear rol por condominio', tags: ['Roles'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Rol creado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function store(RoleStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $role = $condominium->roles()->create([
            'name' => $data['name'],
            'code' => $data['code'] ?? Str::of($data['name'])->slug('_')->toString(),
            'description' => $data['description'] ?? null,
            'is_system' => false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $role->permissions()->sync($data['permission_ids'] ?? []);

        return ApiResponse::success(new RoleResource($role->load('permissions')), 'Rol creado correctamente.', 201);
    }

    #[OA\Put(path: '/api/condominiums/{condominium}/roles/{role}/permissions', operationId: 'rolesSyncPermissions', summary: 'Asignar permisos a rol', tags: ['Roles'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Permisos actualizados'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function syncPermissions(RoleSyncPermissionsRequest $request, Condominium $condominium, Role $role): JsonResponse
    {
        abort_if($role->condominium_id !== $condominium->id, 404);

        $data = $request->validated();

        $role->permissions()->sync(Permission::whereIn('id', $data['permission_ids'])->pluck('id')->all());

        return ApiResponse::success(new RoleResource($role->load('permissions')), 'Permisos del rol actualizados correctamente.');
    }
}
