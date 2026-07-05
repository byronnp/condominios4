<?php

namespace App\Http\Controllers\Api\Units;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Units\UnitIndexRequest;
use App\Http\Requests\Api\Units\UnitStoreRequest;
use App\Http\Requests\Api\Units\UnitUpdateRequest;
use App\Http\Resources\Api\Units\UnitResource;
use App\Models\Condominium;
use App\Models\Unit;
use App\Support\Api\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class UnitController extends Controller
{
    #[OA\Get(path: '/api/condominiums/{condominium}/units', operationId: 'unitsIndex', summary: 'Listar unidades', tags: ['Unidades'], security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1), example: 1), new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100), example: 20)], responses: [new OA\Response(response: 200, description: 'Unidades encontradas'), new OA\Response(response: 422, description: 'Parámetros de paginación inválidos')])]
    public function index(UnitIndexRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();
        $paginator = $condominium->units()
            ->with(['block', 'parentUnit', 'childUnits.unitType', 'unitType'])
            ->orderBy('code')
            ->paginate($data['per_page'] ?? 20);

        return ApiResponse::success(
            UnitResource::collection(collect($paginator->items())),
            'Unidades encontradas.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        );
    }

    #[OA\Post(path: '/api/condominiums/{condominium}/units', operationId: 'unitsStore', summary: 'Crear unidad', tags: ['Unidades'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Unidad creada')])]
    public function store(UnitStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['parent_unit_id'])) {
            $parent = Unit::findOrFail($data['parent_unit_id']);
            abort_if($parent->parent_unit_id !== null, 422, 'No se permite crear unidades de más de dos niveles.');
        }

        $unit = DB::transaction(function () use ($condominium, $data): Unit {
            $unit = $condominium->units()->create([
                'condominium_block_id' => $data['condominium_block_id'] ?? null,
                'parent_unit_id' => $data['parent_unit_id'] ?? null,
                'unit_type_id' => $data['unit_type_id'],
                'code' => $data['code'],
                'number' => $data['number'],
                'floor' => $data['floor'] ?? null,
                'area_m2' => $data['area_m2'] ?? null,
                'current_aliquot_percentage' => $data['current_aliquot_percentage'] ?? null,
                'is_assignable' => $data['is_assignable'] ?? true,
                'is_active' => $data['is_active'] ?? true,
            ]);

            if (isset($data['current_aliquot_percentage'])) {
                $startsOn = CarbonImmutable::parse($data['aliquot_starts_on'] ?? now()->startOfMonth());
                $unit->aliquots()->create([
                    'period_year' => (int) $startsOn->format('Y'),
                    'period_month' => (int) $startsOn->format('m'),
                    'percentage' => $data['current_aliquot_percentage'],
                    'amount' => null,
                    'starts_on' => $startsOn->startOfMonth()->toDateString(),
                    'ends_on' => $startsOn->endOfMonth()->toDateString(),
                    'status' => 'active',
                    'is_active' => true,
                ]);
            }

            return $unit;
        });

        return ApiResponse::success(new UnitResource($unit->load(['block', 'parentUnit', 'unitType', 'aliquots'])), 'Unidad creada correctamente.', 201);
    }

    #[OA\Get(
        path: '/api/condominiums/{condominium}/units/{unit}',
        operationId: 'unitsShow',
        summary: 'Obtener una casa o unidad con sus unidades asociadas',
        description: 'Devuelve el detalle de la unidad y sus unidades hijas en child_units. Los parqueaderos asociados a una casa aparecen en esta colección cuando su parent_unit_id corresponde al ID de la casa.',
        tags: ['Unidades'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'condominium', description: 'ID del condominio', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1), example: 7),
            new OA\Parameter(name: 'unit', description: 'ID de la casa o unidad', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1), example: 27),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Unidad encontrada con sus parqueaderos u otras unidades hijas asociadas',
                content: new OA\JsonContent(example: [
                    'success' => true,
                    'message' => 'Unidad encontrada.',
                    'data' => [
                        'id' => 27,
                        'condominium_id' => 7,
                        'parent_unit_id' => null,
                        'unit_type_id' => 101,
                        'code' => 'CASA-01',
                        'number' => '01',
                        'floor' => null,
                        'area_m2' => '120.00',
                        'current_aliquot_percentage' => '5.0000',
                        'is_assignable' => true,
                        'is_active' => true,
                        'child_units' => [[
                            'id' => 30,
                            'condominium_id' => 7,
                            'parent_unit_id' => 27,
                            'unit_type_id' => 103,
                            'code' => 'P-12',
                            'number' => '12',
                            'area_m2' => '12.50',
                            'is_assignable' => true,
                            'is_active' => true,
                            'unit_type' => [
                                'id' => 103,
                                'code' => 'parqueadero',
                                'name' => 'Parqueadero',
                            ],
                        ]],
                    ],
                    'meta' => [],
                ]),
            ),
            new OA\Response(response: 401, description: 'Token no proporcionado o inválido'),
            new OA\Response(response: 404, description: 'Condominio o unidad no encontrada, o la unidad no pertenece al condominio'),
        ],
    )]
    public function show(Condominium $condominium, Unit $unit): JsonResponse
    {
        abort_if($unit->condominium_id !== $condominium->id, 404);

        return $this->unitResponse($unit);
    }

    #[OA\Patch(
        path: '/api/condominiums/{condominium}/units/{unit}',
        operationId: 'unitsUpdate',
        summary: 'Actualizar una casa o unidad',
        description: 'Actualiza parcialmente una unidad perteneciente al condominio. Requiere ser administrador sénior o tener el permiso units.manage en el condominio.',
        tags: ['Unidades'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'condominium', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1), example: 7),
            new OA\Parameter(name: 'unit', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1), example: 27),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: [
            'number' => '01',
            'area_m2' => 135.50,
            'current_aliquot_percentage' => 6.25,
            'aliquot_starts_on' => '2026-07-01',
            'is_assignable' => true,
            'is_active' => true,
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Unidad actualizada correctamente'),
            new OA\Response(response: 401, description: 'Token no proporcionado o inválido'),
            new OA\Response(response: 403, description: 'Sin permiso para actualizar la unidad'),
            new OA\Response(response: 404, description: 'Condominio o unidad no encontrada, o la unidad no pertenece al condominio'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ],
    )]
    public function update(UnitUpdateRequest $request, Condominium $condominium, Unit $unit): JsonResponse
    {
        abort_if($unit->condominium_id !== $condominium->id, 404);
        Gate::authorize('update', $unit);

        $data = $request->validated();

        DB::transaction(function () use ($unit, $data): void {
            $unit->update(collect($data)->except('aliquot_starts_on')->all());

            if (array_key_exists('current_aliquot_percentage', $data) && $data['current_aliquot_percentage'] !== null) {
                $startsOn = CarbonImmutable::parse($data['aliquot_starts_on']);

                $unit->aliquots()->updateOrCreate([
                    'period_year' => (int) $startsOn->format('Y'),
                    'period_month' => (int) $startsOn->format('m'),
                ], [
                    'percentage' => $data['current_aliquot_percentage'],
                    'amount' => null,
                    'starts_on' => $startsOn->startOfMonth()->toDateString(),
                    'ends_on' => $startsOn->endOfMonth()->toDateString(),
                    'status' => 'active',
                    'is_active' => true,
                ]);
            }
        });

        return ApiResponse::success(
            new UnitResource($unit->fresh()->load(['condominium', 'block', 'parentUnit', 'childUnits.unitType', 'unitType', 'aliquots'])),
            'Unidad actualizada correctamente.'
        );
    }

    #[OA\Get(
        path: '/api/units/{unit}',
        operationId: 'unitsShowById',
        summary: 'Obtener una casa o unidad por ID',
        tags: ['Unidades'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'unit',
                description: 'ID de la casa o unidad',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1),
                example: 1,
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Casa o unidad encontrada',
                content: new OA\JsonContent(example: [
                    'success' => true,
                    'message' => 'Unidad encontrada.',
                    'data' => [
                        'id' => 1,
                        'condominium_id' => 1,
                        'unit_type_id' => 1,
                        'code' => 'CASA-01',
                        'number' => '01',
                        'area_m2' => '120.00',
                        'current_aliquot_percentage' => '5.0000',
                        'is_assignable' => true,
                        'is_active' => true,
                    ],
                ]),
            ),
            new OA\Response(response: 401, description: 'Token no proporcionado o inválido'),
            new OA\Response(response: 404, description: 'Casa o unidad no encontrada'),
        ],
    )]
    public function showById(Unit $unit): JsonResponse
    {
        return $this->unitResponse($unit);
    }

    private function unitResponse(Unit $unit): JsonResponse
    {
        return ApiResponse::success(
            new UnitResource($unit->load(['condominium', 'block', 'parentUnit', 'childUnits.unitType', 'unitType', 'aliquots'])),
            'Unidad encontrada.'
        );
    }

    public function myUnits(Request $request): JsonResponse
    {
        return ApiResponse::success(
            UnitResource::collection($request->user()->units()
                ->wherePivot('is_active', true)
                ->wherePivotNull('deleted_at')
                ->with(['condominium', 'unitType'])
                ->get()),
            'Unidades del usuario encontradas.'
        );
    }
}
