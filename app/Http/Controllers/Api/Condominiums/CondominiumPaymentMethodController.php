<?php

namespace App\Http\Controllers\Api\Condominiums;

use App\Http\Controllers\Controller;
use App\Models\Condominium;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class CondominiumPaymentMethodController extends Controller
{
    #[OA\Get(path: '/api/condominiums/{condominium}/payment-methods', operationId: 'condominiumPaymentMethodsIndex', summary: 'Listar métodos de pago del condominio', tags: ['Métodos de pago'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Métodos de pago encontrados')])]
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            $condominium->paymentMethods()->with('paymentMethodType')->get(),
            'Métodos de pago encontrados.'
        );
    }

    #[OA\Post(path: '/api/condominiums/{condominium}/payment-methods', operationId: 'condominiumPaymentMethodsStore', summary: 'Crear método de pago del condominio', tags: ['Métodos de pago'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Método de pago creado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function store(Request $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validate([
            'catalog_item_id' => ['required', 'integer', Rule::exists('catalog_items', 'id')->where('is_active', true)],
            'account_holder' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_type' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'identification' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'instructions' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (($data['is_default'] ?? false) === true) {
            $condominium->paymentMethods()->update(['is_default' => false]);
        }

        $paymentMethod = $condominium->paymentMethods()->create([
            ...$data,
            'is_default' => $data['is_default'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success($paymentMethod->load('paymentMethodType'), 'Método de pago creado correctamente.', 201);
    }
}
