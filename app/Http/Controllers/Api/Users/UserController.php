<?php

namespace App\Http\Controllers\Api\Users;

use App\Domain\Users\Services\UserService;
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

    #[OA\Get(path: '/api/users', operationId: 'usersIndex', summary: 'Listar usuarios visibles', security: [['bearerAuth' => []]], tags: ['Usuarios'], parameters: [new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), example: 'Ana'), new OA\Parameter(name: 'condominium_id', in: 'query', schema: new OA\Schema(type: 'integer'), example: 1), new OA\Parameter(name: 'role_id', in: 'query', schema: new OA\Schema(type: 'integer'), example: 2), new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['active', 'inactive']), example: 'active')], responses: [new OA\Response(response: 200, description: 'Usuarios encontrados'), new OA\Response(response: 403, description: 'Sin permiso')])]
    public function index(UserIndexRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);
        $data = $request->validated();
        $actor = $request->user();

        if (! $actor->isPlatformAdmin() && isset($data['condominium_id']) && ! in_array((int) $data['condominium_id'], $actor->manageableCondominiumIds('users.view'), true)) {
            abort(403);
        }

        $query = User::query()->with('documentType')->visibleTo($actor)
            ->when($data['search'] ?? null, fn (Builder $q, string $search) => $q->where(fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('document_number', 'like', "%{$search}%")))
            ->when($data['condominium_id'] ?? null, fn (Builder $q, int $id) => $q->whereHas('condominiums', fn (Builder $q) => $q->where('condominiums.id', $id)->wherePivotNull('deleted_at')))
            ->when($data['role_id'] ?? null, fn (Builder $q, int $id) => $q->whereExists(fn ($sub) => $sub->selectRaw('1')->from('condominium_user')->join('condominium_user_role', 'condominium_user_role.condominium_user_id', '=', 'condominium_user.id')->whereColumn('condominium_user.user_id', 'users.id')->where('condominium_user_role.role_id', $id)->whereNull('condominium_user.deleted_at')->whereNull('condominium_user_role.deleted_at')))
            ->when(isset($data['status']), fn (Builder $q) => $q->where('is_access_enabled', $data['status'] === 'active'))
            ->orderBy('name');
        $paginator = $query->paginate($data['per_page'] ?? 20);

        return ApiResponse::success(UserResource::collection(collect($paginator->items())), 'Usuarios encontrados.', meta: ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()]);
    }

    #[OA\Post(
        path: '/api/users',
        operationId: 'usersStore',
        summary: 'Crear usuario administrativo',
        description: 'Crea un usuario desde administración, sin contraseña inicial, y lo asigna a uno o más condominios y roles. Si is_access_enabled no se envía, el usuario queda activo por defecto; para obligar activación por invitación, enviar is_access_enabled=false y luego usar el endpoint de reenvío o creación de invitación según el flujo.',
        security: [['bearerAuth' => []]],
        tags: ['Usuarios'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'country', 'document_type_id', 'document_number', 'assignments'],
                properties: [
                    new OA\Property(property: 'name', description: 'Nombre completo compatible. Puede enviarse en lugar de first_name.', type: 'string', nullable: true, example: 'Ana Pérez'),
                    new OA\Property(property: 'first_name', type: 'string', nullable: true, example: 'Ana'),
                    new OA\Property(property: 'last_name', type: 'string', nullable: true, example: 'Pérez'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'ana@example.com'),
                    new OA\Property(property: 'country', type: 'string', example: 'EC'),
                    new OA\Property(property: 'document_type_id', type: 'integer', example: 1),
                    new OA\Property(property: 'document_number', type: 'string', example: '0912345678'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, example: '0991112222'),
                    new OA\Property(property: 'secondary_phone', type: 'string', nullable: true, example: null),
                    new OA\Property(property: 'is_access_enabled', description: 'Controla si el usuario puede iniciar sesión inmediatamente. En false requiere activación posterior mediante invitación.', type: 'boolean', nullable: true, example: false),
                    new OA\Property(
                        property: 'assignments',
                        type: 'array',
                        items: new OA\Items(
                            required: ['role_id'],
                            properties: [
                                new OA\Property(property: 'condominium_id', description: 'Obligatorio para administradores de plataforma; se infiere para administradores de condominio.', type: 'integer', nullable: true, example: 1),
                                new OA\Property(property: 'role_id', type: 'integer', example: 2),
                            ],
                            type: 'object'
                        )
                    ),
                ],
                type: 'object',
                example: [
                    'first_name' => 'Ana',
                    'last_name' => 'Pérez',
                    'email' => 'ana@example.com',
                    'country' => 'EC',
                    'document_type_id' => 1,
                    'document_number' => '0912345678',
                    'phone' => '0991112222',
                    'is_access_enabled' => false,
                    'assignments' => [
                        ['condominium_id' => 1, 'role_id' => 2],
                    ],
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Usuario creado'),
            new OA\Response(response: 403, description: 'Sin permiso'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function store(StoreUserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        return ApiResponse::success(new UserResource($this->service->create($request->user(), $request->validated())), 'Usuario creado correctamente.', 201);
    }

    #[OA\Get(path: '/api/users/{user}', operationId: 'usersShow', summary: 'Mostrar usuario', security: [['bearerAuth' => []]], tags: ['Usuarios'], parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Usuario encontrado'), new OA\Response(response: 403, description: 'Sin permiso')])]
    public function show(Request $request, User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        return ApiResponse::success(new UserResource($user->load('documentType')), 'Usuario encontrado.');
    }

    #[OA\Put(path: '/api/users/{user}', operationId: 'usersUpdate', summary: 'Actualizar usuario y asignaciones', security: [['bearerAuth' => []]], tags: ['Usuarios'], parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: ['first_name' => 'Ana María', 'assignments' => [['condominium_id' => 1, 'role_id' => 3]]])), responses: [new OA\Response(response: 200, description: 'Usuario actualizado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        return ApiResponse::success(new UserResource($this->service->update($request->user(), $user, $request->validated())), 'Usuario actualizado correctamente.');
    }

    #[OA\Patch(path: '/api/users/{user}/status', operationId: 'usersStatus', summary: 'Activar o inactivar usuario', security: [['bearerAuth' => []]], tags: ['Usuarios'], parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: ['is_access_enabled' => false])), responses: [new OA\Response(response: 200, description: 'Estado actualizado')])]
    public function updateStatus(UserStatusRequest $request, User $user): JsonResponse
    {
        Gate::authorize('updateStatus', $user);
        $this->service->updateStatus(
            $request->user(),
            $user,
            $request->status(),
            $request->header('X-Condominium-Id') ? (int) $request->header('X-Condominium-Id') : null,
        );

        return ApiResponse::success(new UserResource($user->fresh('documentType')), 'Estado del usuario actualizado correctamente.');
    }

    #[OA\Get(path: '/api/users/form-options', operationId: 'usersFormOptions', summary: 'Opciones permitidas para formularios', security: [['bearerAuth' => []]], tags: ['Usuarios'], responses: [new OA\Response(response: 200, description: 'Opciones disponibles')])]
    public function formOptions(Request $request): JsonResponse
    {
        Gate::authorize('create', User::class);
        $actor = $request->user();
        $condominiumIds = $actor->isPlatformAdmin() ? null : $actor->manageableCondominiumIds('users.create');
        $condominiums = Condominium::query()->where('is_active', true)->when($condominiumIds !== null, fn (Builder $q) => $q->whereIn('id', $condominiumIds))->orderBy('name')->get(['id', 'name']);
        $roles = Role::query()->where('is_active', true)->when($condominiumIds !== null, fn (Builder $q) => $q->whereIn('condominium_id', $condominiumIds)->whereIn('code', ['administrador', 'directiva', 'presidente', 'tesorero', 'secretario', 'contabilidad', 'propietario', 'residente']))->orderBy('name')->get(['id', 'condominium_id', 'code', 'name']);
        $documentTypes = CatalogItem::query()->whereHas('catalog', fn (Builder $q) => $q->where('code', 'document_types'))->active()->orderBy('sort_order')->get(['id', 'code', 'name']);

        return ApiResponse::success(['roles' => $roles, 'condominiums' => $condominiums, 'statuses' => [['value' => 'active', 'label' => 'Activo'], ['value' => 'inactive', 'label' => 'Inactivo']], 'document_types' => $documentTypes], 'Opciones de formulario encontradas.');
    }
}
