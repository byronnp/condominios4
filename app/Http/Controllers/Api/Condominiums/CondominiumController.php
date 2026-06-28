<?php

namespace App\Http\Controllers\Api\Condominiums;

use App\Domain\Condominiums\Services\CondominiumCreationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Condominiums\CondominiumOptionIndexRequest;
use App\Http\Requests\Api\Condominiums\CondominiumStatusRequest;
use App\Http\Requests\Api\Condominiums\CondominiumStoreRequest;
use App\Http\Requests\Api\Condominiums\CondominiumUpdateRequest;
use App\Http\Resources\Api\Condominiums\CondominiumResource;
use App\Models\Condominium;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class CondominiumController extends Controller
{
    public function __construct(
        private readonly CondominiumCreationService $creationService,
    ) {}

    #[OA\Get(path: '/api/condominiums', operationId: 'condominiumsIndex', summary: 'Listar condominios', tags: ['Condominios'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Condominios encontrados'), new OA\Response(response: 403, description: 'Acceso permitido solo al administrador senior')])]
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Condominium::class);

        $condominiums = Condominium::query()
            ->visibleTo($request->user())
            ->with(['type', 'country', 'province', 'city', 'features', 'activeBillingSetting', 'users.documentType', 'roles'])
            ->latest()
            ->get();

        return ApiResponse::success(CondominiumResource::collection($condominiums), 'Condominios encontrados.');
    }

    #[OA\Get(
        path: '/api/condominiums/options',
        operationId: 'condominiumsOptions',
        summary: 'Listar opciones de condominios para combos',
        tags: ['Condominios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'search',
                description: 'Filtrar por nombre del condominio.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 255),
                example: 'Cedros',
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Opciones de condominios encontradas'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Acceso permitido solo al administrador senior'),
            new OA\Response(response: 422, description: 'Parámetros inválidos'),
        ]
    )]
    public function options(CondominiumOptionIndexRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Condominium::class);
        $search = $request->validated('search');
        $user = $request->user();

        $options = Condominium::query()
            ->select(['id', 'name'])
            ->where('is_active', true)
            ->visibleTo($user)
            ->when($search, fn ($query, string $search) => $query->where('name', 'like', '%'.trim($search).'%'))
            ->orderBy('name')
            ->get()
            ->map(fn (Condominium $condominium): array => [
                'key' => $condominium->id,
                'value' => $condominium->name,
            ])
            ->values();

        return ApiResponse::success($options, 'Opciones de condominios encontradas.');
    }

    #[OA\Post(
        path: '/api/condominiums',
        operationId: 'condominiumsStore',
        summary: 'Crear condominio',
        tags: ['Condominios'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Enviar como multipart/form-data. Debe incluirse address o direction. El logo se almacena en el filesystem configurado para logos.',
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['name'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'Condominio Vista Verde'),
                        new OA\Property(property: 'slug', description: 'Identificador único. Si se omite, se genera desde name.', type: 'string', nullable: true, example: 'condominio-vista-verde'),
                        new OA\Property(property: 'ruc', type: 'string', nullable: true, example: '0999999999001'),
                        new OA\Property(property: 'type', type: 'string', nullable: true, example: 'Residencial'),
                        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Condominio residencial con áreas comunes y seguridad privada.'),
                        new OA\Property(property: 'status', type: 'string', enum: ['Activo', 'Inactivo'], nullable: true, example: 'Activo'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'administracion@vistaverde.example.com'),
                        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+593 4 555 0101'),
                        new OA\Property(property: 'country_code', type: 'string', nullable: true, example: 'EC'),
                        new OA\Property(property: 'province_id', type: 'integer', nullable: true, example: 9),
                        new OA\Property(property: 'city_id', type: 'integer', nullable: true, example: 101),
                        new OA\Property(property: 'address', description: 'Dirección del condominio. Obligatoria si no se envía direction.', type: 'string', nullable: true, example: 'Av. Principal 123 y Calle Secundaria'),
                        new OA\Property(property: 'direction', description: 'Alias compatible de address. Obligatoria si no se envía address.', type: 'string', nullable: true, example: 'Av. Principal 123 y Calle Secundaria'),
                        new OA\Property(property: 'reference', type: 'string', nullable: true, example: 'Frente al parque central'),
                        new OA\Property(property: 'latitude', type: 'number', format: 'float', nullable: true, example: -2.170998),
                        new OA\Property(property: 'longitude', type: 'number', format: 'float', nullable: true, example: -79.922359),
                        new OA\Property(property: 'currency', type: 'string', nullable: true, example: 'USD'),
                        new OA\Property(property: 'towers', type: 'integer', nullable: true, example: 4),
                        new OA\Property(property: 'houses', type: 'integer', nullable: true, example: 120),
                        new OA\Property(property: 'total_units', type: 'integer', minimum: 0, nullable: true, example: 120),
                        new OA\Property(property: 'is_active', type: 'boolean', nullable: true, example: true),
                        new OA\Property(property: 'characteristics', description: 'IDs de items activos del catálogo condominium_features.', type: 'array', nullable: true, items: new OA\Items(type: 'integer'), example: [1, 2, 3]),
                        new OA\Property(property: 'admin_name', type: 'string', nullable: true, example: 'Carlos'),
                        new OA\Property(property: 'admin_last_name', type: 'string', nullable: true, example: 'Ramírez'),
                        new OA\Property(property: 'admin_document_type', type: 'string', nullable: true, example: 'Cédula'),
                        new OA\Property(property: 'admin_id_number', type: 'string', nullable: true, example: '0912345678'),
                        new OA\Property(property: 'admin_email', type: 'string', format: 'email', nullable: true, example: 'carlos.ramirez@example.com'),
                        new OA\Property(property: 'admin_phone', type: 'string', nullable: true, example: '+593 99 123 4567'),
                        new OA\Property(property: 'admin_status', type: 'string', enum: ['Activo', 'Inactivo'], nullable: true, example: 'Activo'),
                        new OA\Property(property: 'logo', description: 'Imagen del logo. Tamaño máximo: 5 MB.', type: 'string', format: 'binary', nullable: true),
                    ],
                    type: 'object'
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Condominio creado'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
            new OA\Response(response: 500, description: 'Error interno, por ejemplo cuando no es posible almacenar el logo'),
        ]
    )]
    public function store(CondominiumStoreRequest $request): JsonResponse
    {
        Gate::authorize('create', Condominium::class);

        $condominium = $this->creationService->create(
            $request->condominiumData(),
            $request->featureIds(),
            $request->currency(),
            $request->administratorData(),
            $request->file('logo'),
        );

        return ApiResponse::success(new CondominiumResource($condominium), 'Condominio creado correctamente.', 201);
    }

    #[OA\Put(
        path: '/api/condominiums/{condominium}',
        operationId: 'condominiumsUpdate',
        summary: 'Actualizar condominio',
        tags: ['Condominios'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Enviar como multipart/form-data. Todos los campos son opcionales; si se envía una dirección puede usarse address o direction.',
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Condominio Vista Verde Renovado'),
                        new OA\Property(property: 'slug', type: 'string', nullable: true, example: 'condominio-vista-verde-renovado'),
                        new OA\Property(property: 'ruc', type: 'string', nullable: true, example: '0999999999001'),
                        new OA\Property(property: 'type', type: 'string', nullable: true, example: 'Residencial'),
                        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Condominio actualizado con nuevos servicios.'),
                        new OA\Property(property: 'status', type: 'string', enum: ['Activo', 'Inactivo'], nullable: true, example: 'Activo'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'administracion@vistaverde.example.com'),
                        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+593 4 555 0101'),
                        new OA\Property(property: 'country_code', type: 'string', nullable: true, example: 'EC'),
                        new OA\Property(property: 'province_id', type: 'integer', nullable: true, example: 9),
                        new OA\Property(property: 'city_id', type: 'integer', nullable: true, example: 101),
                        new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Av. Principal 456 y Calle Secundaria'),
                        new OA\Property(property: 'direction', type: 'string', nullable: true, example: 'Av. Principal 456 y Calle Secundaria'),
                        new OA\Property(property: 'reference', type: 'string', nullable: true, example: 'Frente al parque renovado'),
                        new OA\Property(property: 'latitude', type: 'number', format: 'float', nullable: true, example: -2.170998),
                        new OA\Property(property: 'longitude', type: 'number', format: 'float', nullable: true, example: -79.922359),
                        new OA\Property(property: 'currency', type: 'string', nullable: true, example: 'USD'),
                        new OA\Property(property: 'towers', type: 'integer', nullable: true, example: 4),
                        new OA\Property(property: 'houses', type: 'integer', nullable: true, example: 120),
                        new OA\Property(property: 'total_units', type: 'integer', minimum: 0, nullable: true, example: 120),
                        new OA\Property(property: 'is_active', type: 'boolean', nullable: true, example: true),
                        new OA\Property(property: 'characteristics', description: 'IDs de items activos del catálogo condominium_features.', type: 'array', nullable: true, items: new OA\Items(type: 'integer'), example: [1, 2, 3]),
                        new OA\Property(property: 'admin_name', type: 'string', nullable: true, example: 'Carlos'),
                        new OA\Property(property: 'admin_last_name', type: 'string', nullable: true, example: 'Ramírez'),
                        new OA\Property(property: 'admin_document_type', type: 'string', nullable: true, example: 'Cédula'),
                        new OA\Property(property: 'admin_id_number', type: 'string', nullable: true, example: '0912345678'),
                        new OA\Property(property: 'admin_email', type: 'string', format: 'email', nullable: true, example: 'carlos.ramirez@example.com'),
                        new OA\Property(property: 'admin_phone', type: 'string', nullable: true, example: '+593 99 123 4567'),
                        new OA\Property(property: 'admin_status', type: 'string', enum: ['Activo', 'Inactivo'], nullable: true, example: 'Activo'),
                        new OA\Property(property: 'logo', description: 'Imagen del logo. Tamaño máximo: 5 MB.', type: 'string', format: 'binary', nullable: true),
                    ],
                    type: 'object'
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Condominio actualizado'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
            new OA\Response(response: 404, description: 'No encontrado'),
            new OA\Response(response: 500, description: 'Error interno, por ejemplo cuando no es posible almacenar el logo'),
        ]
    )]
    public function update(CondominiumUpdateRequest $request, Condominium $condominium): JsonResponse
    {
        Gate::authorize('update', $condominium);

        $condominium = $this->creationService->update(
            $condominium,
            $request->condominiumData(),
            $request->featureIds(),
            $request->currency(),
            $request->administratorData(),
            $request->logo(),
        );

        return ApiResponse::success(new CondominiumResource($condominium), 'Condominio actualizado correctamente.');
    }

    #[OA\Delete(
        path: '/api/condominiums/{condominium}',
        operationId: 'condominiumsDestroy',
        summary: 'Eliminar condominio',
        tags: ['Condominios'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Condominio eliminado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function destroy(Condominium $condominium): JsonResponse
    {
        Gate::authorize('delete', $condominium);

        $this->creationService->delete($condominium);

        return ApiResponse::success(null, 'Condominio eliminado correctamente.');
    }

    #[OA\Get(path: '/api/condominiums/{condominium}', operationId: 'condominiumsShow', summary: 'Obtener condominio', tags: ['Condominios'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Condominio encontrado'), new OA\Response(response: 404, description: 'No encontrado')])]
    public function show(Request $request, Condominium $condominium): JsonResponse
    {
        Gate::authorize('view', $condominium);

        return ApiResponse::success(
            new CondominiumResource($condominium->load(['type', 'country', 'province', 'city', 'features', 'activeBillingSetting', 'users.documentType', 'roles.permissions', 'boards.members.user', 'paymentMethods.paymentMethodType'])),
            'Condominio encontrado.'
        );
    }

    #[OA\Patch(
        path: '/api/condominiums/{condominium}/status',
        operationId: 'condominiumsUpdateStatus',
        summary: 'Activar o inactivar condominio',
        tags: ['Condominios'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['is_active'],
            properties: [new OA\Property(property: 'is_active', type: 'boolean', example: false)]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Estado actualizado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function updateStatus(CondominiumStatusRequest $request, Condominium $condominium): JsonResponse
    {
        Gate::authorize('updateStatus', $condominium);
        $condominium->update($request->validated());

        return ApiResponse::success(
            new CondominiumResource($condominium->fresh()),
            'Estado del condominio actualizado correctamente.'
        );
    }
}
