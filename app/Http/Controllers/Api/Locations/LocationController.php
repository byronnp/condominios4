<?php

namespace App\Http\Controllers\Api\Locations;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Locations\CityResource;
use App\Http\Resources\Api\Locations\CountryResource;
use App\Http\Resources\Api\Locations\ProvinceResource;
use App\Models\Country;
use App\Models\Province;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class LocationController extends Controller
{
    #[OA\Get(
        path: '/api/countries',
        operationId: 'countriesIndex',
        summary: 'Listar países activos',
        tags: ['Ubicaciones'],
        responses: [
            new OA\Response(response: 200, description: 'Países encontrados'),
        ]
    )]
    public function countries(): JsonResponse
    {
        $countries = Country::query()
            ->active()
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            data: CountryResource::collection($countries),
            message: 'Países encontrados.',
        );
    }

    #[OA\Get(
        path: '/api/countries/{country}',
        operationId: 'countriesShow',
        summary: 'Consultar país por código ISO 3166-1 alfa-2',
        tags: ['Ubicaciones'],
        parameters: [
            new OA\Parameter(
                name: 'country',
                description: 'Código ISO alfa-2 del país',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'EC')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'País encontrado'),
            new OA\Response(response: 404, description: 'País no encontrado'),
        ]
    )]
    public function country(Country $country): JsonResponse
    {
        abort_unless($country->is_active, 404);

        $country->load(['provinces' => fn ($query) => $query->active()->orderBy('name')]);

        return ApiResponse::success(
            data: new CountryResource($country),
            message: 'País encontrado.',
        );
    }

    #[OA\Get(
        path: '/api/countries/{country}/provinces',
        operationId: 'countriesProvinces',
        summary: 'Listar provincias activas de un país',
        tags: ['Ubicaciones'],
        parameters: [
            new OA\Parameter(
                name: 'country',
                description: 'Código ISO alfa-2 del país',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'EC')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Provincias encontradas'),
            new OA\Response(response: 404, description: 'País no encontrado'),
        ]
    )]
    public function provinces(Country $country): JsonResponse
    {
        abort_unless($country->is_active, 404);

        $provinces = $country->provinces()
            ->active()
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            data: ProvinceResource::collection($provinces),
            message: 'Provincias encontradas.',
            meta: [
                'country' => [
                    'id' => $country->id,
                    'code' => $country->code,
                    'name' => $country->name,
                ],
            ],
        );
    }

    #[OA\Get(
        path: '/api/provinces/{province}/cities',
        operationId: 'provincesCities',
        summary: 'Listar ciudades activas de una provincia',
        tags: ['Ubicaciones'],
        parameters: [
            new OA\Parameter(
                name: 'province',
                description: 'ID de la provincia',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Ciudades encontradas'),
            new OA\Response(response: 404, description: 'Provincia no encontrada'),
        ]
    )]
    public function cities(Province $province): JsonResponse
    {
        abort_unless($province->is_active, 404);

        $province->load('country');

        $cities = $province->cities()
            ->active()
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            data: CityResource::collection($cities),
            message: 'Ciudades encontradas.',
            meta: [
                'province' => [
                    'id' => $province->id,
                    'code' => $province->code,
                    'name' => $province->name,
                ],
                'country' => [
                    'id' => $province->country->id,
                    'code' => $province->country->code,
                    'name' => $province->country->name,
                ],
            ],
        );
    }
}
