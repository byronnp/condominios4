<?php

namespace App\Http\Controllers\Api\Permissions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Permissions\PermissionStoreRequest;
use App\Http\Requests\Api\Permissions\PermissionUpdateRequest;
use App\Http\Resources\Api\Permissions\PermissionResource;
use App\Models\Permission;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class PermissionController extends Controller
{
    #[OA\Get(path: '/api/permissions', operationId: 'permissionsIndex', summary: 'Listar permisos', tags: ['Permisos'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Permisos encontrados'), new OA\Response(response: 403, description: 'Sin permiso')])]
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Permission::class);

        return ApiResponse::success(
            PermissionResource::collection(Permission::query()->orderBy('module')->orderBy('action')->get()),
            'Permisos encontrados.'
        );
    }

    #[OA\Post(
        path: '/api/permissions',
        operationId: 'permissionsStore',
        summary: 'Crear permiso',
        tags: ['Permisos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['module', 'action', 'name', 'code'],
            example: ['module' => 'roles', 'action' => 'manage', 'name' => 'Administrar roles', 'code' => 'roles.manage', 'description' => 'Permite administrar roles', 'is_active' => true]
        )),
        responses: [new OA\Response(response: 201, description: 'Permiso creado'), new OA\Response(response: 403, description: 'Sin permiso'), new OA\Response(response: 422, description: 'Datos inválidos')]
    )]
    public function store(PermissionStoreRequest $request): JsonResponse
    {
        Gate::authorize('create', Permission::class);

        $data = $request->validated();

        $permission = Permission::create([
            ...$data,
            'is_system' => false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success(new PermissionResource($permission), 'Permiso creado correctamente.', 201);
    }

    #[OA\Put(
        path: '/api/permissions/{permission}',
        operationId: 'permissionsUpdate',
        summary: 'Actualizar permiso',
        tags: ['Permisos'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'permission', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['module', 'action', 'name', 'code'],
            example: ['module' => 'roles', 'action' => 'manage', 'name' => 'Administrar roles', 'code' => 'roles.manage', 'description' => 'Permite administrar roles', 'is_active' => true]
        )),
        responses: [new OA\Response(response: 200, description: 'Permiso actualizado'), new OA\Response(response: 403, description: 'Sin permiso'), new OA\Response(response: 404, description: 'Permiso no encontrado'), new OA\Response(response: 422, description: 'Datos inválidos')]
    )]
    public function update(PermissionUpdateRequest $request, Permission $permission): JsonResponse
    {
        Gate::authorize('update', $permission);

        $permission->update($request->validated());

        return ApiResponse::success(new PermissionResource($permission->fresh()), 'Permiso actualizado correctamente.');
    }

    #[OA\Delete(path: '/api/permissions/{permission}', operationId: 'permissionsDestroy', summary: 'Eliminar permiso', tags: ['Permisos'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'permission', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Permiso eliminado'), new OA\Response(response: 403, description: 'Sin permiso'), new OA\Response(response: 404, description: 'Permiso no encontrado')])]
    public function destroy(Permission $permission): JsonResponse
    {
        Gate::authorize('delete', $permission);

        $permission->delete();

        return ApiResponse::success(message: 'Permiso eliminado correctamente.');
    }
}
