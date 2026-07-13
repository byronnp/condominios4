<?php

namespace App\Http\Controllers\Api\Administrators;

use App\Domain\Administrators\Services\AdministratorService;
use App\Exceptions\Condominiums\CondominiumForbiddenException;
use App\Exceptions\Condominiums\CondominiumInactiveException;
use App\Http\Controllers\Controller;
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

    #[OA\Get(path: '/api/condominiums/{condominium}/administrators', operationId: 'condominiumAdministratorsIndex', summary: 'Listar administradores del condominio', tags: ['Administradores'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Administradores encontrados'), new OA\Response(response: 403, description: 'No autorizado')])]
    public function indexByCondominium(AdministratorIndexRequest $request, Condominium $condominium): JsonResponse
    {
        $this->assertCanUseCondominium($request->user(), $condominium, 'administrators.view');

        $data = $request->validated();
        $query = $this->administratorQuery(false)
            ->when($data['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%");
                });
            })
            ->when(isset($data['status']), fn (Builder $query) => $query->where('is_access_enabled', $data['status'] === 'active'))
            ->orderBy('name');

        $this->whereAdministratorInCondominiums($query, [$condominium->id]);

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

    #[OA\Post(path: '/api/condominiums/{condominium}/administrators', operationId: 'condominiumAdministratorsStore', summary: 'Crear administrador del condominio', tags: ['Administradores'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: ['first_name' => 'Carlos', 'last_name' => 'Ramírez', 'email' => 'carlos.ramirez@example.com', 'country' => 'EC', 'document_type_id' => 1, 'document_number' => '0912345678', 'phone' => '0991234567'])), responses: [new OA\Response(response: 201, description: 'Administrador creado'), new OA\Response(response: 403, description: 'No autorizado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function storeInCondominium(AdministratorStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $this->assertCanUseCondominium($request->user(), $condominium, 'administrators.create');

        $administrator = $this->administratorService->assignUserAsAdministrator($condominium, $request->validated(), $request->user());

        return ApiResponse::success(new AdministratorResource($administrator), 'Administrador creado e invitación enviada correctamente.', 201);
    }

    #[OA\Get(path: '/api/condominiums/{condominium}/administrators/{user}', operationId: 'condominiumAdministratorsShow', summary: 'Consultar administrador del condominio', tags: ['Administradores'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Administrador encontrado'), new OA\Response(response: 403, description: 'No autorizado'), new OA\Response(response: 404, description: 'No encontrado')])]
    public function showInCondominium(Request $request, Condominium $condominium, User $user): JsonResponse
    {
        $this->assertAdministratorInCondominium($user, $condominium);
        $this->assertCanUseCondominium($request->user(), $condominium, 'administrators.view');

        return ApiResponse::success(new AdministratorResource($user->load('documentType')), 'Administrador encontrado.');
    }

    #[OA\Put(path: '/api/condominiums/{condominium}/administrators/{user}', operationId: 'condominiumAdministratorsUpdate', summary: 'Actualizar administrador del condominio', tags: ['Administradores'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Administrador actualizado'), new OA\Response(response: 403, description: 'No autorizado'), new OA\Response(response: 404, description: 'No encontrado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function updateInCondominium(AdministratorUpdateRequest $request, Condominium $condominium, User $user): JsonResponse
    {
        $this->assertAdministratorInCondominium($user, $condominium);
        $this->assertCanUseCondominium($request->user(), $condominium, 'administrators.update');

        $user->update($request->validated());

        return ApiResponse::success(new AdministratorResource($user->fresh('documentType')), 'Administrador actualizado correctamente.');
    }

    #[OA\Patch(path: '/api/condominiums/{condominium}/administrators/{user}/status', operationId: 'condominiumAdministratorsUpdateStatus', summary: 'Activar o inactivar administrador del condominio', tags: ['Administradores'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Estado actualizado'), new OA\Response(response: 403, description: 'No autorizado'), new OA\Response(response: 404, description: 'No encontrado')])]
    public function updateStatusInCondominium(AdministratorStatusRequest $request, Condominium $condominium, User $user): JsonResponse
    {
        $this->assertAdministratorInCondominium($user, $condominium);
        $this->assertCanUseCondominium($request->user(), $condominium, 'administrators.update');

        $user->update($request->validated());

        return ApiResponse::success(new AdministratorResource($user->fresh('documentType')), 'Estado del administrador actualizado correctamente.');
    }

    #[OA\Delete(path: '/api/condominiums/{condominium}/administrators/{user}', operationId: 'condominiumAdministratorsDestroy', summary: 'Desvincular administrador del condominio', tags: ['Administradores'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Administrador desvinculado'), new OA\Response(response: 403, description: 'No autorizado'), new OA\Response(response: 404, description: 'No encontrado')])]
    public function destroyInCondominium(Request $request, Condominium $condominium, User $user): JsonResponse
    {
        $this->assertAdministratorInCondominium($user, $condominium);
        $this->assertCanUseCondominium($request->user(), $condominium, 'administrators.delete');

        $this->administratorService->removeFromCondominium($user, $condominium);

        return ApiResponse::success(message: 'Administrador eliminado correctamente.');
    }

    private function administratorQuery(bool $includePlatformAdministrators = false): Builder
    {
        return User::query()
            ->with(['documentType', 'latestAccessInvitation'])
            ->where(function (Builder $query) use ($includePlatformAdministrators): void {
                $query->whereExists(function ($query): void {
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

                if ($includePlatformAdministrators) {
                    $query->orWhereExists(function ($query): void {
                        $query->selectRaw('1')
                            ->from('role_user')
                            ->join('roles', 'roles.id', '=', 'role_user.role_id')
                            ->whereColumn('role_user.user_id', 'users.id')
                            ->whereNull('roles.condominium_id')
                            ->whereIn('roles.code', ['administrador_senior', 'admin'])
                            ->where('roles.is_active', true)
                            ->whereNull('roles.deleted_at');
                    });
                }
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

    private function assertAdministrator(User $administrator, bool $includePlatformAdministrators = false): void
    {
        abort_if(! $this->administratorQuery($includePlatformAdministrators)->whereKey($administrator->id)->exists(), 404);
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

    private function assertCanUseCondominium(User $user, Condominium $condominium, string $permission): void
    {
        if (! $condominium->is_active) {
            throw new CondominiumInactiveException;
        }

        if ($user->isPlatformAdmin()) {
            return;
        }

        if (! in_array($condominium->id, $this->accessibleCondominiumIds($user, $permission), true)) {
            throw new CondominiumForbiddenException;
        }
    }

    private function assertAdministratorInCondominium(User $administrator, Condominium $condominium): void
    {
        abort_if(! DB::table('condominium_user')
            ->join('condominium_user_role', 'condominium_user_role.condominium_user_id', '=', 'condominium_user.id')
            ->join('roles', 'roles.id', '=', 'condominium_user_role.role_id')
            ->where('condominium_user.user_id', $administrator->id)
            ->where('condominium_user.condominium_id', $condominium->id)
            ->where('roles.code', 'administrador')
            ->whereNull('condominium_user.deleted_at')
            ->whereNull('condominium_user_role.deleted_at')
            ->whereNull('roles.deleted_at')
            ->exists(), 404);
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
