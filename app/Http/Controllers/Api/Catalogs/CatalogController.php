<?php

namespace App\Http\Controllers\Api\Catalogs;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Catalogs\CatalogItemResource;
use App\Http\Resources\Api\Catalogs\CatalogResource;
use App\Models\Catalog;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class CatalogController extends Controller
{
    #[OA\Get(
        path: '/api/catalogs',
        operationId: 'catalogsIndex',
        summary: 'Listar catálogos generales activos',
        tags: ['Catálogos'],
        responses: [
            new OA\Response(response: 200, description: 'Catálogos encontrados'),
        ]
    )]
    public function index(): JsonResponse
    {
        $catalogs = Catalog::query()
            ->active()
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            data: CatalogResource::collection($catalogs),
            message: 'Catálogos encontrados.',
        );
    }

    #[OA\Get(
        path: '/api/catalogs/{catalog}',
        operationId: 'catalogsShow',
        summary: 'Consultar un catálogo general por código',
        tags: ['Catálogos'],
        parameters: [
            new OA\Parameter(
                name: 'catalog',
                description: 'Código del catálogo',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'document_types')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Catálogo encontrado'),
            new OA\Response(response: 404, description: 'Catálogo no encontrado'),
        ]
    )]
    public function show(string $catalog): JsonResponse
    {
        $catalogModel = Catalog::query()
            ->active()
            ->where('code', $catalog)
            ->with(['items' => fn ($query) => $query->active()->orderBy('sort_order')->orderBy('name')])
            ->firstOrFail();

        return ApiResponse::success(
            data: new CatalogResource($catalogModel),
            message: 'Catálogo encontrado.',
        );
    }

    #[OA\Get(
        path: '/api/catalogs/{catalog}/items',
        operationId: 'catalogsItems',
        summary: 'Listar items activos de un catálogo general',
        tags: ['Catálogos'],
        parameters: [
            new OA\Parameter(
                name: 'catalog',
                description: 'Código del catálogo',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'document_types')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Items encontrados'),
            new OA\Response(response: 404, description: 'Catálogo no encontrado'),
        ]
    )]
    public function items(string $catalog): JsonResponse
    {
        $catalogModel = Catalog::query()
            ->active()
            ->where('code', $catalog)
            ->firstOrFail();

        $items = $catalogModel->items()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            data: CatalogItemResource::collection($items),
            message: 'Items de catálogo encontrados.',
            meta: [
                'catalog' => [
                    'id' => $catalogModel->id,
                    'code' => $catalogModel->code,
                    'name' => $catalogModel->name,
                ],
            ],
        );
    }
}
