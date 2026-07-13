<?php

namespace App\Http\Controllers\Api\Menus;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Menus\MenuStoreRequest;
use App\Http\Resources\Api\Menus\MenuResource;
use App\Models\Condominium;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class MenuController extends Controller
{
    #[OA\Get(path: '/api/menus', operationId: 'menusIndex', summary: 'Listar menús', tags: ['Menús'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Menús encontrados'), new OA\Response(response: 403, description: 'Solo administrador senior')])]
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Menu::class);

        return ApiResponse::success(
            MenuResource::collection(Menu::query()
                ->with(['children.permissions', 'permissions'])
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get()),
            'Menús encontrados.'
        );
    }

    #[OA\Post(path: '/api/menus', operationId: 'menusStore', summary: 'Crear menú padre o hijo', tags: ['Menús'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Menú creado'), new OA\Response(response: 403, description: 'Solo administrador senior'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function store(MenuStoreRequest $request): JsonResponse
    {
        Gate::authorize('create', Menu::class);

        $data = $request->validated();

        if (isset($data['parent_id'])) {
            $parent = Menu::findOrFail($data['parent_id']);
            abort_if($parent->parent_id !== null, 422, 'No se permite crear menús de más de dos niveles.');
        }

        $menu = Menu::create([
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'code' => $data['code'] ?? Str::of($data['name'])->slug('_')->toString(),
            'path' => $data['path'] ?? null,
            'icon' => $data['icon'] ?? null,
            'category_code' => isset($data['parent_id']) ? null : ($data['category_code'] ?? null),
            'category_name' => isset($data['parent_id']) ? null : ($data['category_name'] ?? null),
            'category_sort_order' => isset($data['parent_id']) ? 0 : ($data['category_sort_order'] ?? 0),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $menu->permissions()->sync($data['permission_ids'] ?? []);

        return ApiResponse::success(new MenuResource($menu->load('permissions')), 'Menú creado correctamente.', 201);
    }

    #[OA\Get(path: '/api/auth/menu', operationId: 'authMenu', summary: 'Obtener menú del usuario autenticado', tags: ['Menús'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Menú obtenido'), new OA\Response(response: 403, description: 'Condominio no permitido o inactivo'), new OA\Response(response: 422, description: 'Contexto de condominio requerido')])]
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        $condominium = $this->resolveCondominiumForMenu($request, $user);

        if ($condominium instanceof JsonResponse) {
            return $condominium;
        }

        if (! $condominium && ! $user->isPlatformAdmin()) {
            return ApiResponse::success(MenuResource::collection(collect()), 'Menú obtenido correctamente.');
        }

        $permissionCodes = $user->isPlatformAdmin()
            ? Permission::query()->where('is_active', true)->pluck('code')->all()
            : $user->permissionsForCondominium($condominium)
                ->where('code', '!=', 'condominiums.view')
                ->pluck('code')
                ->all();

        $parents = Menu::query()
            ->with(['children.permissions', 'permissions'])
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('category_sort_order')
            ->orderBy('category_name')
            ->orderBy('sort_order')
            ->get()
            ->map(function (Menu $parent) use ($permissionCodes): ?array {
                $required = $parent->permissions->pluck('code');
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

                if ($children->isEmpty() && ! $parent->path) {
                    return null;
                }

                if ($children->isEmpty() && $required->isNotEmpty() && $required->intersect($permissionCodes)->isEmpty()) {
                    return null;
                }

                return [
                    'id' => $parent->id,
                    'name' => $parent->name,
                    'code' => $parent->code,
                    'icon' => $parent->icon,
                    'path' => $parent->path,
                    'children' => $children,
                    'category' => [
                        'code' => $parent->category_code ?? 'sin_categoria',
                        'name' => $parent->category_name ?? 'Sin categoría',
                        'sort_order' => $parent->category_sort_order,
                    ],
                ];
            })
            ->filter()
            ->groupBy('category.code')
            ->map(function ($menus): array {
                $first = $menus->first();

                return [
                    'code' => $first['category']['code'],
                    'name' => $first['category']['name'],
                    'sort_order' => $first['category']['sort_order'],
                    'menus' => $menus
                        ->map(fn (array $menu): array => [
                            'id' => $menu['id'],
                            'name' => $menu['name'],
                            'code' => $menu['code'],
                            'icon' => $menu['icon'],
                            'path' => $menu['path'],
                            'children' => $menu['children']->values(),
                        ])
                        ->values(),
                ];
            })
            ->sortBy('sort_order')
            ->values();

        return ApiResponse::success(MenuResource::collection($parents), 'Menú obtenido correctamente.');
    }

    private function resolveCondominiumForMenu(Request $request, User $user): Condominium|JsonResponse|null
    {
        $headerCondominiumId = $request->header('X-Condominium-Id');

        if ($user->isPlatformAdmin()) {
            if (! $headerCondominiumId) {
                return null;
            }

            $condominium = Condominium::query()->whereKey($headerCondominiumId)->first();

            if (! $condominium) {
                return ApiResponse::error('El condominio solicitado no existe.', 404, code: 'condominium_not_found');
            }

            if (! $condominium->is_active) {
                return ApiResponse::error('El condominio está inactivo.', 403, code: 'condominium_inactive');
            }

            return $condominium;
        }

        $activeCondominiums = $user->condominiums()
            ->wherePivot('is_active', true)
            ->wherePivotNull('deleted_at')
            ->where('condominiums.is_active', true)
            ->get();

        if ($headerCondominiumId) {
            $condominium = $activeCondominiums->firstWhere('id', (int) $headerCondominiumId);

            if (! $condominium) {
                $belongsToInactive = $user->condominiums()
                    ->whereKey($headerCondominiumId)
                    ->wherePivotNull('deleted_at')
                    ->first();

                if ($belongsToInactive && ! $belongsToInactive->is_active) {
                    return ApiResponse::error('El condominio está inactivo.', 403, code: 'condominium_inactive');
                }

                return ApiResponse::error('El condominio no pertenece al usuario autenticado.', 403, code: 'condominium_forbidden');
            }

            return $condominium;
        }

        if ($activeCondominiums->count() === 1) {
            return $activeCondominiums->first();
        }

        if ($activeCondominiums->count() > 1) {
            return ApiResponse::error(
                'Debes indicar el condominio activo para consultar el menú.',
                422,
                code: 'condominium_context_required',
            );
        }

        $hasInactiveMemberships = $user->condominiums()->wherePivotNull('deleted_at')->exists();

        if ($hasInactiveMemberships) {
            return ApiResponse::error('El condominio está inactivo.', 403, code: 'condominium_inactive');
        }

        return null;
    }
}
