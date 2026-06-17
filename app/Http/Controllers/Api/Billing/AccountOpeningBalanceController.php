<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Billing\AccountOpeningBalanceStoreRequest;
use App\Http\Resources\Api\Billing\AccountOpeningBalanceResource;
use App\Models\Condominium;
use App\Models\CondominiumAccountOpeningBalance;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class AccountOpeningBalanceController extends Controller
{
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            AccountOpeningBalanceResource::collection(CondominiumAccountOpeningBalance::where('condominium_id', $condominium->id)->with('paymentMethod')->get()),
            'Saldos iniciales encontrados.'
        );
    }

    public function store(AccountOpeningBalanceStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $balance = CondominiumAccountOpeningBalance::create([
            ...$data,
            'condominium_id' => $condominium->id,
            'registered_by_user_id' => $request->user()?->id,
            'is_active' => true,
        ]);

        return ApiResponse::success(new AccountOpeningBalanceResource($balance), 'Saldo inicial registrado correctamente.', 201);
    }
}
