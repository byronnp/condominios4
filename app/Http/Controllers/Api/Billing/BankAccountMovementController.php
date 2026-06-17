<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Billing\BankAccountMovementStoreRequest;
use App\Http\Resources\Api\Billing\BankAccountMovementResource;
use App\Models\BankAccountMovement;
use App\Models\Condominium;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class BankAccountMovementController extends Controller
{
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(BankAccountMovementResource::collection(BankAccountMovement::where('condominium_id', $condominium->id)->latest('movement_date')->get()), 'Movimientos bancarios encontrados.');
    }

    public function store(BankAccountMovementStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $movement = BankAccountMovement::create([
            ...$data,
            'condominium_id' => $condominium->id,
            'registered_by_user_id' => $request->user()?->id,
        ]);

        return ApiResponse::success(new BankAccountMovementResource($movement), 'Movimiento bancario registrado correctamente.', 201);
    }
}
