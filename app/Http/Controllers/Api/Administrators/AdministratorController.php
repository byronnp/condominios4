<?php

namespace App\Http\Controllers\Api\Administrators;

use App\Domain\Administrators\Services\AdministratorService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Administrators\AdministratorAssignCondominiumRequest;
use App\Http\Requests\Api\Administrators\AdministratorIndexRequest;
use App\Http\Requests\Api\Administrators\AdministratorStatusRequest;
use App\Http\Requests\Api\Administrators\AdministratorStoreRequest;
use App\Http\Requests\Api\Administrators\AdministratorUpdateRequest;
use App\Http\Resources\Api\Administrators\AdministratorResource;
use App\Models\Condominium;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class AdministratorController extends Controller
{
    public function __construct(
        private readonly AdministratorService $administratorService,
    ) {}

    #[OA\Get(
        path: '/api/administrators',
        operationId: 'administratorsIndex',
        summary: 'Listar administradores',
        description: 'Devuelve administradores con su estado de acceso, tipo y última invitación de activación.',
        tags: ['Administradores'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), example: 'Carlos'),
            new OA\Parameter(name: 'condominium_id', in: 'query', schema: new OA\Schema(type: 'integer'), example: 1),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['active', 'inactive']), example: 'active'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1), example: 1),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100), example: 20),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Administradores encontrados'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function index(AdministratorIndexRequest $request): JsonResponse
    {
        $data = $request->validated();
        $accessibleCondominiumIds = $this->accessibleCondominiumIds($request->user(), 'administrators.view');

        abort_if(! $request->user()->isPlatformAdmin() && $accessibleCondominiumIds === [], 403);

        $query = $this->administratorQuery()
            ->when(! $request->user()->isPlatformAdmin(), function (Builder $query) use ($accessibleCondominiumIds): void {
                $this->whereAdministratorInCondominiums($query, $accessibleCondominiumIds);
            })
            ->when($data['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%");
                });
            })
            ->when($data['condominium_id'] ?? null, function (Builder $query, int $condominiumId) use ($request, $accessibleCondominiumIds): void {
                abort_if(! $request->user()->isPlatformAdmin() && ! in_array($condominiumId, $accessibleCondominiumIds, true), 403);
                $this->whereAdministratorInCondominiums($query, [$condominiumId]);
            })
            ->when(isset($data['status']), fn (Builder $query) => $query->where('is_access_enabled', $data['status'] === 'active'))
            ->orderBy('name');

        $paginator = $query->paginate($data['per_page'] ?? 20);

        return ApiResponse::success(
            AdministratorResource::collection(collect($paginator->items())),
            'Administradores encontrados.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        );
    }

    #[OA\Post(
        path: '/api/administrators',
        operationId: 'administratorsStore',
        summary: 'Crear administrador',
        description: 'Crea el administrador con acceso deshabilitado, lo asigna a los condominios indicados y envía por correo una invitación de activación de 24 horas.',
        tags: ['Administradores'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['email', 'country', 'document_type_id', 'document_number', 'condominium_ids'],
            properties: [
                new OA\Property(property: 'name', description: 'Nombre completo compatible. Puede enviarse en lugar de first_name.', type: 'string', nullable: true, example: 'Carlos Ramírez'),
                new OA\Property(property: 'first_name', description: 'Nombres. Obligatorio cuando no se envía name.', type: 'string', nullable: true, example: 'Carlos'),
                new OA\Property(property: 'last_name', type: 'string', nullable: true, example: 'Ramírez'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'carlos.ramirez@example.com'),
                new OA\Property(property: 'country', type: 'string', example: 'EC'),
                new OA\Property(property: 'document_type_id', type: 'integer', example: 1),
                new OA\Property(property: 'document_number', type: 'string', example: '0912345678'),
                new OA\Property(property: 'phone', type: 'string', nullable: true, example: '0991234567'),
                new OA\Property(property: 'secondary_phone', type: 'string', nullable: true, example: '042345678'),
                new OA\Property(property: 'condominium_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1]),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Administrador creado e invitación enviada'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function store(AdministratorStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->assertCanManageCondominiums($request->user(), $data['condominium_ids'], 'administrators.create');

        $administrator = $this->administratorService->create($data, $request->user());

        return ApiResponse::success(new AdministratorResource($administrator), 'Administrador creado e invitación enviada correctamente.', 201);
    }

    #[OA\Get(path: '/api/administrators/{administrator}', operationId: 'administratorsShow', summary: 'Consultar administrador', tags: ['Administradores'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Administrador encontrado'), new OA\Response(response: 404, description: 'No encontrado')])]
    public function show(Request $request, User $administrator): JsonResponse
    {
        $this->assertAdministrator($administrator);
        $this->assertCanAccessAdministrator($request->user(), $administrator, 'administrators.view');

        return ApiResponse::success(new AdministratorResource($administrator->load('documentType')), 'Administrador encontrado.');
    }

    #[OA\Put(
        path: '/api/administrators/{administrator}',
        operationId: 'administratorsUpdate',
        summary: 'Actualizar administrador',
        tags: ['Administradores'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Carlos Ramírez'),
                new OA\Property(property: 'first_name', type: 'string', example: 'Carlos'),
                new OA\Property(property: 'last_name', type: 'string', nullable: true, example: 'Ramírez'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'carlos.ramirez@example.com'),
                new OA\Property(property: 'country', type: 'string', example: 'EC'),
                new OA\Property(property: 'document_type_id', type: 'integer', example: 1),
                new OA\Property(property: 'document_number', type: 'string', example: '0912345678'),
                new OA\Property(property: 'phone', type: 'string', nullable: true, example: '0991234567'),
                new OA\Property(property: 'secondary_phone', type: 'string', nullable: true, example: '042345678'),
            ]
        )),
        responses: [new OA\Response(response: 200, description: 'Administrador actualizado'), new OA\Response(response: 422, description: 'Datos inválidos')]
    )]
    public function update(AdministratorUpdateRequest $request, User $administrator): JsonResponse
    {
        $this->assertAdministrator($administrator);
        $this->assertCanManageAdministrator($request->user(), $administrator, 'administrators.update');

        $administrator->update($request->validated());

        return ApiResponse::success(new AdministratorResource($administrator->fresh('documentType')), 'Administrador actualizado correctamente.');
    }

    #[OA\Patch(
        path: '/api/administrators/{administrator}/status',
        operationId: 'administratorsUpdateStatus',
        summary: 'Activar o inactivar acceso de administrador',
        tags: ['Administradores'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['is_access_enabled'],
            properties: [new OA\Property(property: 'is_access_enabled', type: 'boolean', example: true)]
        )),
        responses: [new OA\Response(response: 200, description: 'Estado actualizado')]
    )]
    public function updateStatus(AdministratorStatusRequest $request, User $administrator): JsonResponse
    {
        $this->assertAdministrator($administrator);
        $this->assertCanManageAdministrator($request->user(), $administrator, 'administrators.update');

        $administrator->update($request->validated());

        return ApiResponse::success(new AdministratorResource($administrator->fresh('documentType')), 'Estado del administrador actualizado correctamente.');
    }

    #[OA\Post(
        path: '/api/administrators/{administrator}/condominiums',
        operationId: 'administratorsAssignCondominium',
        summary: 'Asignar administrador a condominio',
        tags: ['Administradores'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['condominium_id'],
            properties: [new OA\Property(property: 'condominium_id', type: 'integer', example: 1)]
        )),
        responses: [new OA\Response(response: 200, description: 'Administrador asignado')]
    )]
    public function assignCondominium(AdministratorAssignCondominiumRequest $request, User $administrator): JsonResponse
    {
        $this->assertAdministrator($administrator);
        $condominium = Condominium::findOrFail($request->integer('condominium_id'));
        $this->assertCanManageCondominiums($request->user(), [$condominium->id], 'administrators.assign');

        $this->administratorService->assignToCondominium($administrator, $condominium);

        return ApiResponse::success(new AdministratorResource($administrator->fresh('documentType')), 'Administrador asignado al condominio correctamente.');
    }

    #[OA\Delete(path: '/api/administrators/{administrator}/condominiums/{condominium}', operationId: 'administratorsRemoveCondominium', summary: 'Desvincular administrador de condominio', tags: ['Administradores'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Administrador desvinculado')])]
    public function removeCondominium(Request $request, User $administrator, Condominium $condominium): JsonResponse
    {
        $this->assertAdministrator($administrator);
        $this->assertCanManageCondominiums($request->user(), [$condominium->id], 'administrators.assign');

        $this->administratorService->removeFromCondominium($administrator, $condominium);

        return ApiResponse::success(new AdministratorResource($administrator->fresh('documentType')), 'Administrador desvinculado del condominio correctamente.');
    }

    #[OA\Delete(path: '/api/administrators/{administrator}', operationId: 'administratorsDestroy', summary: 'Desactivar administrador y retirar sus asignaciones', tags: ['Administradores'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Administrador eliminado')])]
    public function destroy(Request $request, User $administrator): JsonResponse
    {
        $this->assertAdministrator($administrator);
        $this->assertCanManageAdministrator($request->user(), $administrator, 'administrators.delete');

        $this->administratorService->deactivate($administrator);

        return ApiResponse::success(message: 'Administrador eliminado correctamente.');
    }

    private function administratorQuery(): Builder
    {
        return User::query()
            ->with(['documentType', 'latestAccessInvitation'])
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('condominium_user')
                    ->join('condominium_user_role', 'condominium_user_role.condominium_user_id', '=', 'condominium_user.id')
                    ->join('roles', 'roles.id', '=', 'condominium_user_role.role_id')
                    ->whereColumn('condominium_user.user_id', 'users.id')
                    ->where('roles.code', 'administrador')
                    ->whereNull('condominium_user.deleted_at')
                    ->whereNull('condominium_user_role.deleted_at')
                    ->whereNull('roles.deleted_at');
            });
    }

    /**
     * @param  array<int, int>  $condominiumIds
     */
    private function whereAdministratorInCondominiums(Builder $query, array $condominiumIds): void
    {
        $query->whereExists(function ($query) use ($condominiumIds): void {
            $query->selectRaw('1')
                ->from('condominium_user')
                ->join('condominium_user_role', 'condominium_user_role.condominium_user_id', '=', 'condominium_user.id')
                ->join('roles', 'roles.id', '=', 'condominium_user_role.role_id')
                ->whereColumn('condominium_user.user_id', 'users.id')
                ->whereIn('condominium_user.condominium_id', $condominiumIds)
                ->where('roles.code', 'administrador')
                ->whereNull('condominium_user.deleted_at')
                ->whereNull('condominium_user_role.deleted_at')
                ->whereNull('roles.deleted_at');
        });
    }

    private function assertAdministrator(User $administrator): void
    {
        abort_if(! $this->administratorQuery()->whereKey($administrator->id)->exists(), 404);
    }

    private function assertCanAccessAdministrator(User $user, User $administrator, string $permission): void
    {
        if ($user->isPlatformAdmin()) {
            return;
        }

        $accessibleIds = $this->accessibleCondominiumIds($user, $permission);

        abort_if($accessibleIds === [] || ! DB::table('condominium_user')
            ->join('condominium_user_role', 'condominium_user_role.condominium_user_id', '=', 'condominium_user.id')
            ->join('roles', 'roles.id', '=', 'condominium_user_role.role_id')
            ->where('condominium_user.user_id', $administrator->id)
            ->whereIn('condominium_user.condominium_id', $accessibleIds)
            ->where('roles.code', 'administrador')
            ->whereNull('condominium_user.deleted_at')
            ->whereNull('condominium_user_role.deleted_at')
            ->exists(), 403);
    }

    private function assertCanManageAdministrator(User $user, User $administrator, string $permission): void
    {
        if ($user->isPlatformAdmin()) {
            return;
        }

        $administratorCondominiumIds = $this->administratorCondominiumIds($administrator);
        $accessibleIds = $this->accessibleCondominiumIds($user, $permission);

        abort_if(
            $administratorCondominiumIds === []
            || array_diff($administratorCondominiumIds, $accessibleIds) !== [],
            403,
        );
    }

    /**
     * @param  array<int, int>  $condominiumIds
     */
    private function assertCanManageCondominiums(User $user, array $condominiumIds, string $permission): void
    {
        if ($user->isPlatformAdmin()) {
            return;
        }

        $accessibleIds = $this->accessibleCondominiumIds($user, $permission);
        $requestedIds = array_map('intval', $condominiumIds);

        abort_if(array_diff($requestedIds, $accessibleIds) !== [], 403);
    }

    /**
     * @return array<int, int>
     */
    private function accessibleCondominiumIds(User $user, string $permission): array
    {
        return DB::table('condominium_user')
            ->join('condominium_user_role', 'condominium_user_role.condominium_user_id', '=', 'condominium_user.id')
            ->join('role_permission', 'role_permission.role_id', '=', 'condominium_user_role.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('condominium_user.user_id', $user->id)
            ->where('condominium_user.is_active', true)
            ->where('permissions.code', $permission)
            ->whereNull('condominium_user.deleted_at')
            ->whereNull('condominium_user_role.deleted_at')
            ->whereNull('role_permission.deleted_at')
            ->whereNull('permissions.deleted_at')
            ->distinct()
            ->pluck('condominium_user.condominium_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function administratorCondominiumIds(User $administrator): array
    {
        return DB::table('condominium_user')
            ->join('condominium_user_role', 'condominium_user_role.condominium_user_id', '=', 'condominium_user.id')
            ->join('roles', 'roles.id', '=', 'condominium_user_role.role_id')
            ->where('condominium_user.user_id', $administrator->id)
            ->where('roles.code', 'administrador')
            ->whereNull('condominium_user.deleted_at')
            ->whereNull('condominium_user_role.deleted_at')
            ->whereNull('roles.deleted_at')
            ->distinct()
            ->pluck('condominium_user.condominium_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
