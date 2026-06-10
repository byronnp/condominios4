<?php

namespace App\Http\Controllers\Api\Roles;

use App\Http\Controllers\Controller;
use App\Models\Condominium;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class RoleController extends Controller
{
    #[OA\Get(path: '/api/condominiums/{condominium}/roles', operationId: 'rolesIndex', summary: 'Listar roles por condominio', tags: ['Roles'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Roles encontrados')])]
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            $condominium->roles()->with('permissions')->orderBy('name')->get(),
            'Roles encontrados.'
        );
    }

    #[OA\Post(path: '/api/condominiums/{condominium}/roles', operationId: 'rolesStore', summary: 'Crear rol por condominio', tags: ['Roles'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Rol creado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function store(Request $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('roles', 'code')->where('condominium_id', $condominium->id),
            ],
            'description' => ['nullable', 'string'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')->where('is_active', true)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $role = $condominium->roles()->create([
            'name' => $data['name'],
            'code' => $data['code'] ?? Str::of($data['name'])->slug('_')->toString(),
            'description' => $data['description'] ?? null,
            'is_system' => false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $role->permissions()->sync($data['permission_ids'] ?? []);

        return ApiResponse::success($role->load('permissions'), 'Rol creado correctamente.', 201);
    }

    #[OA\Put(path: '/api/condominiums/{condominium}/roles/{role}/permissions', operationId: 'rolesSyncPermissions', summary: 'Asignar permisos a rol', tags: ['Roles'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Permisos actualizados'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function syncPermissions(Request $request, Condominium $condominium, Role $role): JsonResponse
    {
        abort_if($role->condominium_id !== $condominium->id, 404);

        $data = $request->validate([
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')->where('is_active', true)],
        ]);

        $role->permissions()->sync(Permission::whereIn('id', $data['permission_ids'])->pluck('id')->all());

        return ApiResponse::success($role->load('permissions'), 'Permisos del rol actualizados correctamente.');
    }
}
