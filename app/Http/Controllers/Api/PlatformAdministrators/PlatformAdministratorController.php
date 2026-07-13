<?php

namespace App\Http\Controllers\Api\PlatformAdministrators;

use App\Domain\PlatformAdministrators\Services\PlatformAdministratorService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PlatformAdministrators\PlatformAdministratorIndexRequest;
use App\Http\Requests\Api\PlatformAdministrators\PlatformAdministratorStatusRequest;
use App\Http\Requests\Api\PlatformAdministrators\PlatformAdministratorStoreRequest;
use App\Http\Requests\Api\PlatformAdministrators\PlatformAdministratorUpdateRequest;
use App\Http\Resources\Api\PlatformAdministrators\PlatformAdministratorResource;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class PlatformAdministratorController extends Controller
{
    public function __construct(
        private readonly PlatformAdministratorService $service,
    ) {}

    #[OA\Get(path: '/api/platform-administrators', operationId: 'platformAdministratorsIndex', summary: 'Listar administradores senior', tags: ['Administradores de Plataforma'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Administradores encontrados'), new OA\Response(response: 403, description: 'No autorizado')])]
    public function index(PlatformAdministratorIndexRequest $request): JsonResponse
    {
        Gate::authorize('platform-administrators.viewAny');

        $data = $request->validated();
        $query = $this->platformAdministratorQuery()
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

        $paginator = $query->paginate($data['per_page'] ?? 20);

        return ApiResponse::success(
            PlatformAdministratorResource::collection(collect($paginator->items())),
            'Administradores de plataforma encontrados.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        );
    }

    #[OA\Post(path: '/api/platform-administrators', operationId: 'platformAdministratorsStore', summary: 'Crear o asignar administrador senior', tags: ['Administradores de Plataforma'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: ['first_name' => 'Ana', 'last_name' => 'Senior', 'email' => 'ana.senior@example.com', 'country' => 'EC', 'document_type_id' => 1, 'document_number' => '0912345678', 'phone' => '0991234567'])), responses: [new OA\Response(response: 201, description: 'Administrador creado o asignado'), new OA\Response(response: 403, description: 'No autorizado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function store(PlatformAdministratorStoreRequest $request): JsonResponse
    {
        Gate::authorize('platform-administrators.create');

        $result = $this->service->createOrAssign($request->validated(), $request->user());
        $message = $result['already_assigned']
            ? 'El usuario ya es administrador de plataforma.'
            : 'Administrador de plataforma creado correctamente.';

        return ApiResponse::success(
            new PlatformAdministratorResource($result['user']),
            $message,
            $result['already_assigned'] ? 200 : 201,
        );
    }

    #[OA\Get(path: '/api/platform-administrators/{user}', operationId: 'platformAdministratorsShow', summary: 'Consultar administrador senior', tags: ['Administradores de Plataforma'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Administrador encontrado'), new OA\Response(response: 403, description: 'No autorizado'), new OA\Response(response: 404, description: 'No encontrado')])]
    public function show(User $user): JsonResponse
    {
        $this->assertPlatformAdministrator($user);
        Gate::authorize('platform-administrators.view', $user);

        return ApiResponse::success(
            new PlatformAdministratorResource($user->load(['documentType', 'latestAccessInvitation'])),
            'Administrador de plataforma encontrado.',
        );
    }

    #[OA\Patch(path: '/api/platform-administrators/{user}', operationId: 'platformAdministratorsUpdate', summary: 'Actualizar administrador senior', tags: ['Administradores de Plataforma'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: ['first_name' => 'Ana Maria', 'phone' => '0987654321'])), responses: [new OA\Response(response: 200, description: 'Administrador actualizado'), new OA\Response(response: 403, description: 'No autorizado'), new OA\Response(response: 404, description: 'No encontrado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function update(PlatformAdministratorUpdateRequest $request, User $user): JsonResponse
    {
        $this->assertPlatformAdministrator($user);
        Gate::authorize('platform-administrators.update', $user);

        $user->update($request->validated());

        return ApiResponse::success(
            new PlatformAdministratorResource($user->fresh(['documentType', 'latestAccessInvitation'])),
            'Administrador de plataforma actualizado correctamente.',
        );
    }

    #[OA\Patch(path: '/api/platform-administrators/{user}/status', operationId: 'platformAdministratorsUpdateStatus', summary: 'Activar o desactivar administrador senior', tags: ['Administradores de Plataforma'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: ['is_access_enabled' => false])), responses: [new OA\Response(response: 200, description: 'Estado actualizado'), new OA\Response(response: 403, description: 'No autorizado'), new OA\Response(response: 404, description: 'No encontrado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function updateStatus(PlatformAdministratorStatusRequest $request, User $user): JsonResponse
    {
        $this->assertPlatformAdministrator($user);
        Gate::authorize('platform-administrators.updateStatus', $user);

        $administrator = $this->service->updateStatus($user, (bool) $request->validated('is_access_enabled'), $request->user());

        return ApiResponse::success(
            new PlatformAdministratorResource($administrator),
            'Estado del administrador de plataforma actualizado correctamente.',
        );
    }

    #[OA\Delete(path: '/api/platform-administrators/{user}', operationId: 'platformAdministratorsDestroy', summary: 'Eliminar rol de administrador senior', tags: ['Administradores de Plataforma'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Rol eliminado'), new OA\Response(response: 403, description: 'No autorizado'), new OA\Response(response: 404, description: 'No encontrado')])]
    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->assertPlatformAdministrator($user);
        Gate::authorize('platform-administrators.delete', $user);

        $this->service->removeRole($user, $request->user());

        return ApiResponse::success(message: 'Administrador de plataforma eliminado correctamente.');
    }

    private function platformAdministratorQuery(): Builder
    {
        return User::query()
            ->with(['documentType', 'latestAccessInvitation'])
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('role_user')
                    ->join('roles', 'roles.id', '=', 'role_user.role_id')
                    ->whereColumn('role_user.user_id', 'users.id')
                    ->whereNull('roles.condominium_id')
                    ->where('roles.code', 'administrador_senior')
                    ->where('roles.is_active', true)
                    ->whereNull('roles.deleted_at');
            });
    }

    private function assertPlatformAdministrator(User $user): void
    {
        abort_if(! $this->platformAdministratorQuery()->whereKey($user->id)->exists(), 404);
    }
}
