<?php

namespace App\Http\Controllers\Api\Roles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Roles\RoleStoreRequest;
use App\Http\Requests\Api\Roles\RoleSyncPermissionsRequest;
use App\Http\Requests\Api\Roles\RoleUpdateRequest;
use App\Http\Resources\Api\Roles\RoleResource;
use App\Models\Condominium;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class RoleController extends Controller
{
    #[OA\Get(path: '/api/condominiums/{condominium}/roles', operationId: 'rolesIndex', summary: 'Listar roles por condominio', tags: ['Roles'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'condominium', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Roles encontrados'), new OA\Response(response: 403, description: 'Sin permiso')])]
    public function index(Condominium $condominium): JsonResponse
    {
        Gate::authorize('viewAny', [Role::class, $condominium]);

        return ApiResponse::success(
            RoleResource::collection($condominium->roles()->with('permissions')->orderBy('name')->get()),
            'Roles encontrados.'
        );
    }

    #[OA\Get(path: '/api/condominiums/{condominium}/roles/{role}', operationId: 'rolesShow', summary: 'Obtener rol por condominio', tags: ['Roles'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'condominium', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Rol encontrado'), new OA\Response(response: 403, description: 'Sin permiso'), new OA\Response(response: 404, description: 'Rol no encontrado')])]
    public function show(Condominium $condominium, Role $role): JsonResponse
    {
        abort_if($role->condominium_id !== $condominium->id, 404);
        Gate::authorize('view', [$role, $condominium]);

        return ApiResponse::success(new RoleResource($role->load('permissions')), 'Rol encontrado.');
    }

    #[OA\Post(
        path: '/api/condominiums/{condominium}/roles',
        operationId: 'rolesStore',
        summary: 'Crear rol por condominio',
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'condominium', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name'],
            example: ['name' => 'Supervisor', 'code' => 'supervisor', 'description' => 'Rol de supervisión', 'is_active' => true]
        )),
        responses: [new OA\Response(response: 201, description: 'Rol creado'), new OA\Response(response: 403, description: 'Sin permiso'), new OA\Response(response: 422, description: 'Datos inválidos')]
    )]
    public function store(RoleStoreRequest $request, Condominium $condominium): JsonResponse
    {
        Gate::authorize('create', [Role::class, $condominium]);

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

    #[OA\Put(
        path: '/api/condominiums/{condominium}/roles/{role}',
        operationId: 'rolesUpdate',
        summary: 'Actualizar rol por condominio',
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'condominium', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name'],
            example: ['name' => 'Supervisor general', 'code' => 'supervisor_general', 'description' => 'Rol actualizado', 'is_active' => true]
        )),
        responses: [new OA\Response(response: 200, description: 'Rol actualizado'), new OA\Response(response: 403, description: 'Sin permiso'), new OA\Response(response: 404, description: 'Rol no encontrado'), new OA\Response(response: 422, description: 'Datos inválidos')]
    )]
    public function update(RoleUpdateRequest $request, Condominium $condominium, Role $role): JsonResponse
    {
        abort_if($role->condominium_id !== $condominium->id, 404);
        Gate::authorize('update', [$role, $condominium]);

        $data = $request->validated();

        $role->fill([
            'name' => $data['name'],
            'code' => $data['code'] ?? $role->code,
            'description' => array_key_exists('description', $data) ? $data['description'] : $role->description,
            'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : $role->is_active,
        ])->save();

        return ApiResponse::success(new RoleResource($role->fresh('permissions')), 'Rol actualizado correctamente.');
    }

    #[OA\Delete(path: '/api/condominiums/{condominium}/roles/{role}', operationId: 'rolesDestroy', summary: 'Eliminar rol por condominio', tags: ['Roles'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'condominium', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Rol eliminado'), new OA\Response(response: 403, description: 'Sin permiso'), new OA\Response(response: 404, description: 'Rol no encontrado')])]
    public function destroy(Condominium $condominium, Role $role): JsonResponse
    {
        abort_if($role->condominium_id !== $condominium->id, 404);
        Gate::authorize('delete', [$role, $condominium]);

        $role->delete();

        return ApiResponse::success(message: 'Rol eliminado correctamente.');
    }

    #[OA\Put(
        path: '/api/condominiums/{condominium}/roles/{role}/permissions',
        operationId: 'rolesSyncPermissions',
        summary: 'Asignar permisos a rol',
        tags: ['Roles'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'condominium', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['permission_ids'], example: ['permission_ids' => [1, 2, 3]])),
        responses: [new OA\Response(response: 200, description: 'Permisos actualizados'), new OA\Response(response: 403, description: 'Sin permiso'), new OA\Response(response: 404, description: 'Rol no encontrado'), new OA\Response(response: 422, description: 'Datos inválidos')]
    )]
    public function syncPermissions(RoleSyncPermissionsRequest $request, Condominium $condominium, Role $role): JsonResponse
    {
        abort_if($role->condominium_id !== $condominium->id, 404);
        Gate::authorize('managePermissions', [$role, $condominium]);

        $data = $request->validated();

        $role->permissions()->sync(Permission::whereIn('id', $data['permission_ids'])->pluck('id')->all());

        return ApiResponse::success(new RoleResource($role->load('permissions')), 'Permisos del rol actualizados correctamente.');
    }
}
