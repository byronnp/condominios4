<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Billing\PaymentOrderStoreRequest;
use App\Http\Resources\Api\Billing\PaymentOrderResource;
use App\Models\Condominium;
use App\Models\PaymentOrder;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class PaymentOrderController extends Controller
{
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(PaymentOrderResource::collection(PaymentOrder::where('condominium_id', $condominium->id)->latest()->get()), 'Órdenes de pago encontradas.');
    }

    public function store(PaymentOrderStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $order = PaymentOrder::create([
            ...$data,
            'condominium_id' => $condominium->id,
            'status' => 'pending',
            'expires_at' => $data['expires_at'] ?? now()->addDay(),
        ]);

        return ApiResponse::success(new PaymentOrderResource($order), 'Orden de pago creada correctamente.', 201);
    }
}
