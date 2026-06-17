<?php

namespace App\Http\Controllers\Api\Condominiums;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Condominiums\CondominiumPaymentMethodStoreRequest;
use App\Http\Resources\Api\Condominiums\CondominiumPaymentMethodResource;
use App\Models\Condominium;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class CondominiumPaymentMethodController extends Controller
{
    #[OA\Get(path: '/api/condominiums/{condominium}/payment-methods', operationId: 'condominiumPaymentMethodsIndex', summary: 'Listar métodos de pago del condominio', tags: ['Métodos de pago'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Métodos de pago encontrados')])]
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            CondominiumPaymentMethodResource::collection($condominium->paymentMethods()->with('paymentMethodType')->get()),
            'Métodos de pago encontrados.'
        );
    }

    #[OA\Post(path: '/api/condominiums/{condominium}/payment-methods', operationId: 'condominiumPaymentMethodsStore', summary: 'Crear método de pago del condominio', tags: ['Métodos de pago'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Método de pago creado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function store(CondominiumPaymentMethodStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        if (($data['is_default'] ?? false) === true) {
            $condominium->paymentMethods()->update(['is_default' => false]);
        }

        $paymentMethod = $condominium->paymentMethods()->create([
            ...$data,
            'is_default' => $data['is_default'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success(new CondominiumPaymentMethodResource($paymentMethod->load('paymentMethodType')), 'Método de pago creado correctamente.', 201);
    }
}
