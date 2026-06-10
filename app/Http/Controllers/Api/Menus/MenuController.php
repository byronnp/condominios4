<?php

namespace App\Http\Controllers\Api\Menus;

use App\Http\Controllers\Controller;
use App\Models\Condominium;
use App\Models\Menu;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class MenuController extends Controller
{
    #[OA\Get(path: '/api/menus', operationId: 'menusIndex', summary: 'Listar menús', tags: ['Menús'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Menús encontrados')])]
    public function index(): JsonResponse
    {
        return ApiResponse::success(
            Menu::query()
                ->with(['children.permissions', 'permissions'])
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get(),
            'Menús encontrados.'
        );
    }

    #[OA\Post(path: '/api/menus', operationId: 'menusStore', summary: 'Crear menú padre o hijo', tags: ['Menús'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Menú creado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:menus,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', 'unique:menus,code'],
            'path' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')->where('is_active', true)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (isset($data['parent_id'])) {
            $parent = Menu::findOrFail($data['parent_id']);
            abort_if($parent->parent_id !== null, 422, 'No se permite crear menús de más de dos niveles.');
            validator($data, ['path' => ['required', 'string', 'max:255']])->validate();
        }

        $menu = Menu::create([
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'code' => $data['code'] ?? Str::of($data['name'])->slug('_')->toString(),
            'path' => $data['path'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $menu->permissions()->sync($data['permission_ids'] ?? []);

        return ApiResponse::success($menu->load('permissions'), 'Menú creado correctamente.', 201);
    }

    #[OA\Get(path: '/api/auth/menu', operationId: 'authMenu', summary: 'Obtener menú del usuario autenticado', tags: ['Menús'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Menú obtenido')])]
    public function current(Request $request): JsonResponse
    {
        $condominium = Condominium::query()
            ->whereKey($request->header('X-Condominium-Id'))
            ->first()
            ?? $request->user()->condominiums()->first();

        if (! $condominium) {
            return ApiResponse::success([], 'Menú obtenido correctamente.');
        }

        $permissionCodes = $request->user()
            ->permissionsForCondominium($condominium)
            ->pluck('code')
            ->all();

        $parents = Menu::query()
            ->with(['children.permissions'])
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (Menu $parent) use ($permissionCodes): ?array {
                $children = $parent->children
                    ->filter(function (Menu $child) use ($permissionCodes): bool {
                        $required = $child->permissions->pluck('code');

                        return $child->is_active && ($required->isEmpty() || $required->intersect($permissionCodes)->isNotEmpty());
                    })
                    ->values()
                    ->map(fn (Menu $child): array => [
                        'id' => $child->id,
                        'name' => $child->name,
                        'code' => $child->code,
                        'icon' => $child->icon,
                        'path' => $child->path,
                    ]);

                if ($children->isEmpty()) {
                    return null;
                }

                return [
                    'id' => $parent->id,
                    'name' => $parent->name,
                    'code' => $parent->code,
                    'icon' => $parent->icon,
                    'path' => $parent->path,
                    'children' => $children,
                ];
            })
            ->filter()
            ->values();

        return ApiResponse::success($parents, 'Menú obtenido correctamente.');
    }
}
