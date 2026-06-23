<?php

namespace App\Http\Controllers\Api\Condominiums;

use App\Domain\Condominiums\Services\CondominiumCreationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Condominiums\CondominiumStoreRequest;
use App\Http\Resources\Api\Condominiums\CondominiumResource;
use App\Models\Condominium;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class CondominiumController extends Controller
{
    public function __construct(
        private readonly CondominiumCreationService $creationService,
    ) {}

    #[OA\Get(path: '/api/condominiums', operationId: 'condominiumsIndex', summary: 'Listar condominios', tags: ['Condominios'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Condominios encontrados')])]
    public function index(): JsonResponse
    {
        $condominiums = Condominium::query()
            ->with(['type', 'country', 'province', 'city', 'features', 'activeBillingSetting', 'users.documentType', 'roles'])
            ->latest()
            ->get();

        return ApiResponse::success(CondominiumResource::collection($condominiums), 'Condominios encontrados.');
    }

    #[OA\Post(
        path: '/api/condominiums',
        operationId: 'condominiumsStore',
        summary: 'Crear condominio',
        tags: ['Condominios'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['name', 'direction'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'Condominio Vista Verde'),
                        new OA\Property(property: 'ruc', type: 'string', nullable: true, example: '0999999999001'),
                        new OA\Property(property: 'type', type: 'string', nullable: true, example: 'Residencial'),
                        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Condominio residencial con áreas comunes y seguridad privada.'),
                        new OA\Property(property: 'status', type: 'string', enum: ['Activo', 'Inactivo'], nullable: true, example: 'Activo'),
                        new OA\Property(property: 'country_code', type: 'string', nullable: true, example: 'EC'),
                        new OA\Property(property: 'province_id', type: 'integer', nullable: true, example: 9),
                        new OA\Property(property: 'city_id', type: 'integer', nullable: true, example: 101),
                        new OA\Property(property: 'direction', type: 'string', example: 'Av. Principal 123 y Calle Secundaria'),
                        new OA\Property(property: 'reference', type: 'string', nullable: true, example: 'Frente al parque central'),
                        new OA\Property(property: 'latitude', type: 'number', format: 'float', nullable: true, example: -2.170998),
                        new OA\Property(property: 'longitude', type: 'number', format: 'float', nullable: true, example: -79.922359),
                        new OA\Property(property: 'currency', type: 'string', nullable: true, example: 'USD'),
                        new OA\Property(property: 'towers', type: 'integer', nullable: true, example: 4),
                        new OA\Property(property: 'houses', type: 'integer', nullable: true, example: 120),
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
        ]
    )]
    public function store(CondominiumStoreRequest $request): JsonResponse
    {
        $condominium = $this->creationService->create(
            $request->condominiumData(),
            $request->featureIds(),
            $request->currency(),
            $request->administratorData(),
            $request->file('logo'),
        );

        return ApiResponse::success(new CondominiumResource($condominium), 'Condominio creado correctamente.', 201);
    }

    #[OA\Get(path: '/api/condominiums/{condominium}', operationId: 'condominiumsShow', summary: 'Obtener condominio', tags: ['Condominios'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Condominio encontrado'), new OA\Response(response: 404, description: 'No encontrado')])]
    public function show(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            new CondominiumResource($condominium->load(['type', 'country', 'province', 'city', 'features', 'activeBillingSetting', 'users.documentType', 'roles.permissions', 'boards.members.user', 'paymentMethods.paymentMethodType'])),
            'Condominio encontrado.'
        );
    }
}
