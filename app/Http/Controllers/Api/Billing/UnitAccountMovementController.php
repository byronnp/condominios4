<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Billing\UnitAccountMovementResource;
use App\Models\Condominium;
use App\Models\Unit;
use App\Models\UnitAccountMovement;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class UnitAccountMovementController extends Controller
{
    public function index(Condominium $condominium, Unit $unit): JsonResponse
    {
        abort_if($unit->condominium_id !== $condominium->id, 404);

        return ApiResponse::success(
            UnitAccountMovementResource::collection(UnitAccountMovement::where('unit_id', $unit->id)->latest()->get()),
            'Movimientos de cuenta encontrados.'
        );
    }
}
