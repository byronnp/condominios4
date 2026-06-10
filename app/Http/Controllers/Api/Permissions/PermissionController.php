<?php

namespace App\Http\Controllers\Api\Permissions;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class PermissionController extends Controller
{
    #[OA\Get(path: '/api/permissions', operationId: 'permissionsIndex', summary: 'Listar permisos', tags: ['Permisos'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Permisos encontrados')])]
    public function index(): JsonResponse
    {
        return ApiResponse::success(
            Permission::query()->orderBy('module')->orderBy('action')->get(),
            'Permisos encontrados.'
        );
    }

    #[OA\Post(path: '/api/permissions', operationId: 'permissionsStore', summary: 'Crear permiso', tags: ['Permisos'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Permiso creado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'module' => ['required', 'string', 'max:100'],
            'action' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:150', 'regex:/^[a-z0-9_]+\.[a-z0-9_]+$/', 'unique:permissions,code'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['code'] ??= $data['module'].'.'.$data['action'];
        validator($data, [
            'code' => ['required', 'string', 'max:150', 'regex:/^[a-z0-9_]+\.[a-z0-9_]+$/', Rule::unique('permissions', 'code')],
        ])->validate();

        $permission = Permission::create([
            ...$data,
            'is_system' => false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success($permission, 'Permiso creado correctamente.', 201);
    }
}
