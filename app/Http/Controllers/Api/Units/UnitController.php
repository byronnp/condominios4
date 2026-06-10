<?php

namespace App\Http\Controllers\Api\Units;

use App\Http\Controllers\Controller;
use App\Models\Condominium;
use App\Models\Unit;
use App\Rules\ValidCatalogItem;
use App\Support\Api\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class UnitController extends Controller
{
    #[OA\Get(path: '/api/condominiums/{condominium}/units', operationId: 'unitsIndex', summary: 'Listar unidades', tags: ['Unidades'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Unidades encontradas')])]
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            $condominium->units()
                ->with(['block', 'parentUnit', 'childUnits.unitType', 'unitType'])
                ->orderBy('code')
                ->get(),
            'Unidades encontradas.'
        );
    }

    #[OA\Post(path: '/api/condominiums/{condominium}/units', operationId: 'unitsStore', summary: 'Crear unidad', tags: ['Unidades'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Unidad creada')])]
    public function store(Request $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validate([
            'condominium_block_id' => ['nullable', 'integer', Rule::exists('condominium_blocks', 'id')->where('condominium_id', $condominium->id)],
            'parent_unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where('condominium_id', $condominium->id)],
            'unit_type_id' => ['required', 'integer', new ValidCatalogItem('unit_types')],
            'code' => ['required', 'string', 'max:100', Rule::unique('units', 'code')->where('condominium_id', $condominium->id)],
            'number' => ['required', 'string', 'max:100'],
            'floor' => ['nullable', 'string', 'max:50'],
            'area_m2' => ['nullable', 'numeric', 'min:0'],
            'current_aliquot_percentage' => ['nullable', 'numeric', 'min:0'],
            'aliquot_starts_on' => ['nullable', 'date'],
            'is_assignable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

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

        return ApiResponse::success($unit->load(['block', 'parentUnit', 'unitType', 'aliquots']), 'Unidad creada correctamente.', 201);
    }

    #[OA\Get(path: '/api/condominiums/{condominium}/units/{unit}', operationId: 'unitsShow', summary: 'Obtener unidad', tags: ['Unidades'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Unidad encontrada')])]
    public function show(Condominium $condominium, Unit $unit): JsonResponse
    {
        abort_if($unit->condominium_id !== $condominium->id, 404);

        return ApiResponse::success(
            $unit->load(['block', 'parentUnit', 'childUnits.unitType', 'unitType', 'aliquots']),
            'Unidad encontrada.'
        );
    }

    public function myUnits(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $request->user()->units()
                ->wherePivot('is_active', true)
                ->wherePivotNull('deleted_at')
                ->with(['condominium', 'unitType'])
                ->get(),
            'Unidades del usuario encontradas.'
        );
    }
}
