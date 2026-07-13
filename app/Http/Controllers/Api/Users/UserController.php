<?php

namespace App\Http\Controllers\Api\Users;

use App\Domain\Users\Services\UserService;
use App\Exceptions\Condominiums\CondominiumForbiddenException;
use App\Exceptions\Condominiums\CondominiumInactiveException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Users\StoreUserRequest;
use App\Http\Requests\Api\Users\UpdateUserRequest;
use App\Http\Requests\Api\Users\UserIndexRequest;
use App\Http\Requests\Api\Users\UserStatusRequest;
use App\Http\Resources\Api\Users\UserResource;
use App\Models\CatalogItem;
use App\Models\Condominium;
use App\Models\Role;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Usuarios', description: 'CRUD unificado de usuarios y asignaciones por condominio')]
class UserController extends Controller
{
    public function __construct(private readonly UserService $service) {}

    #[OA\Get(path: '/api/condominiums/{condominium}/users', operationId: 'condominiumUsersIndex', summary: 'Listar usuarios del condominio', security: [['bearerAuth' => []]], tags: ['Usuarios'], responses: [new OA\Response(response: 200, description: 'Usuarios encontrados'), new OA\Response(response: 403, description: 'Sin permiso')])]
    public function indexByCondominium(UserIndexRequest $request, Condominium $condominium): JsonResponse
    {
        $this->authorizeCondominium($request->user(), $condominium, 'users.view', 'viewAnyInCondominium', [User::class, $condominium]);

        $data = $request->validated();
        $query = User::query()
            ->with('documentType')
            ->whereHas('condominiums', fn (Builder $q) => $q
                ->where('condominiums.id', $condominium->id)
                ->whereNull('condominium_user.deleted_at'))
            ->when($data['search'] ?? null, fn (Builder $q, string $search) => $q->where(fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('document_number', 'like', "%{$search}%")))
            ->when($data['role_id'] ?? null, fn (Builder $q, int $id) => $q->whereExists(fn ($sub) => $sub->selectRaw('1')->from('condominium_user')->join('condominium_user_role', 'condominium_user_role.condominium_user_id', '=', 'condominium_user.id')->whereColumn('condominium_user.user_id', 'users.id')->where('condominium_user.condominium_id', $condominium->id)->where('condominium_user_role.role_id', $id)->whereNull('condominium_user.deleted_at')->whereNull('condominium_user_role.deleted_at')))
            ->when(isset($data['status']), fn (Builder $q) => $q->where('is_access_enabled', $data['status'] === 'active'))
            ->orderBy('name');
        $paginator = $query->paginate($data['per_page'] ?? 20);

        return ApiResponse::success(UserResource::collection(collect($paginator->items())), 'Usuarios encontrados.', meta: ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()]);
    }

    #[OA\Post(path: '/api/condominiums/{condominium}/users', operationId: 'condominiumUsersStore', summary: 'Crear usuario en condominio', security: [['bearerAuth' => []]], tags: ['Usuarios'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: ['first_name' => 'Ana', 'last_name' => 'Pérez', 'email' => 'ana@example.com', 'country' => 'EC', 'document_type_id' => 1, 'document_number' => '0912345678', 'phone' => '0991112222', 'is_access_enabled' => false, 'role_id' => 2])), responses: [new OA\Response(response: 201, description: 'Usuario creado'), new OA\Response(response: 403, description: 'Sin permiso'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function storeInCondominium(StoreUserRequest $request, Condominium $condominium): JsonResponse
    {
        $this->authorizeCondominium($request->user(), $condominium, 'users.create', 'createInCondominium', [User::class, $condominium]);

        return ApiResponse::success(new UserResource($this->service->createInCondominium($request->user(), $condominium, $request->validated())), 'Usuario creado correctamente.', 201);
    }

    #[OA\Get(path: '/api/condominiums/{condominium}/users/{user}', operationId: 'condominiumUsersShow', summary: 'Mostrar usuario del condominio', security: [['bearerAuth' => []]], tags: ['Usuarios'], responses: [new OA\Response(response: 200, description: 'Usuario encontrado'), new OA\Response(response: 403, description: 'Sin permiso'), new OA\Response(response: 404, description: 'No encontrado')])]
    public function showInCondominium(Request $request, Condominium $condominium, User $user): JsonResponse
    {
        $this->assertUserBelongsToCondominium($user, $condominium);
        $this->authorizeCondominium($request->user(), $condominium, 'users.view', 'viewInCondominium', [$user, $condominium]);

        return ApiResponse::success(new UserResource($user->load('documentType')), 'Usuario encontrado.');
    }

    #[OA\Put(path: '/api/condominiums/{condominium}/users/{user}', operationId: 'condominiumUsersUpdate', summary: 'Actualizar usuario del condominio', security: [['bearerAuth' => []]], tags: ['Usuarios'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: ['first_name' => 'Ana María', 'role_id' => 3])), responses: [new OA\Response(response: 200, description: 'Usuario actualizado'), new OA\Response(response: 403, description: 'Sin permiso'), new OA\Response(response: 404, description: 'No encontrado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function updateInCondominium(UpdateUserRequest $request, Condominium $condominium, User $user): JsonResponse
    {
        $this->assertUserBelongsToCondominium($user, $condominium);
        $this->authorizeCondominium($request->user(), $condominium, 'users.update', 'updateInCondominium', [$user, $condominium]);

        return ApiResponse::success(new UserResource($this->service->updateInCondominium($request->user(), $user, $condominium, $request->validated())), 'Usuario actualizado correctamente.');
    }

    #[OA\Patch(path: '/api/condominiums/{condominium}/users/{user}/status', operationId: 'condominiumUsersStatus', summary: 'Activar o inactivar usuario en condominio', security: [['bearerAuth' => []]], tags: ['Usuarios'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: ['is_access_enabled' => false])), responses: [new OA\Response(response: 200, description: 'Estado actualizado'), new OA\Response(response: 403, description: 'Sin permiso'), new OA\Response(response: 404, description: 'No encontrado')])]
    public function updateStatusInCondominium(UserStatusRequest $request, Condominium $condominium, User $user): JsonResponse
    {
        $this->assertUserBelongsToCondominium($user, $condominium, false);
        $this->authorizeCondominium($request->user(), $condominium, 'users.status', 'updateStatusInCondominium', [$user, $condominium]);
        $this->service->updateStatusInCondominium($request->user(), $user, $condominium, $request->status());

        return ApiResponse::success(new UserResource($user->fresh('documentType')), 'Estado del usuario actualizado correctamente.');
    }

    #[OA\Get(path: '/api/condominiums/{condominium}/users/form-options', operationId: 'condominiumUsersFormOptions', summary: 'Opciones permitidas para formularios de usuarios del condominio', security: [['bearerAuth' => []]], tags: ['Usuarios'], responses: [new OA\Response(response: 200, description: 'Opciones disponibles')])]
    public function formOptions(Request $request, Condominium $condominium): JsonResponse
    {
        $this->authorizeCondominium($request->user(), $condominium, 'users.create', 'createInCondominium', [User::class, $condominium]);
        $actor = $request->user();
        $condominiums = collect([$condominium->only(['id', 'name'])]);
        $roles = Role::query()
            ->where('is_active', true)
            ->where('condominium_id', $condominium->id)
            ->when(! $actor->isPlatformAdmin(), fn (Builder $q) => $q->whereIn('code', ['administrador', 'directiva', 'presidente', 'tesorero', 'secretario', 'contabilidad', 'propietario', 'residente']))
            ->orderBy('name')
            ->get(['id', 'condominium_id', 'code', 'name']);
        $documentTypes = CatalogItem::query()->whereHas('catalog', fn (Builder $q) => $q->where('code', 'document_types'))->active()->orderBy('sort_order')->get(['id', 'code', 'name']);

        return ApiResponse::success(['roles' => $roles, 'condominiums' => $condominiums, 'statuses' => [['value' => 'active', 'label' => 'Activo'], ['value' => 'inactive', 'label' => 'Inactivo']], 'document_types' => $documentTypes], 'Opciones de formulario encontradas.');
    }

    private function authorizeCondominium(User $actor, Condominium $condominium, string $permission, string $ability, array $arguments): void
    {
        if (! $condominium->is_active) {
            throw new CondominiumInactiveException;
        }

        if (! Gate::allows($ability, $arguments)) {
            throw new CondominiumForbiddenException;
        }
    }

    private function assertUserBelongsToCondominium(User $user, Condominium $condominium, bool $onlyActive = true): void
    {
        $query = $user->condominiums()
            ->where('condominiums.id', $condominium->id)
            ->wherePivotNull('deleted_at');

        if ($onlyActive) {
            $query->wherePivot('is_active', true);
        }

        abort_if(! $query->exists(), 404);
    }
}
