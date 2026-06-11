<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Billing\BillingSettingStoreRequest;
use App\Models\Condominium;
use App\Models\CondominiumBillingSetting;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class BillingSettingController extends Controller
{
    #[OA\Get(path: '/api/condominiums/{condominium}/billing-settings', operationId: 'billingSettingsShow', summary: 'Obtener configuración económica', tags: ['Economía'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Configuración encontrada')])]
    public function show(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            $condominium->hasOne(CondominiumBillingSetting::class)->where('is_active', true)->first(),
            'Configuración económica encontrada.'
        );
    }

    public function store(BillingSettingStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $condominium->hasMany(CondominiumBillingSetting::class)->update(['is_active' => false]);
        $setting = $condominium->hasMany(CondominiumBillingSetting::class)->create([
            ...$data,
            'currency' => $data['currency'] ?? 'USD',
            'rounding_mode' => $data['rounding_mode'] ?? 'round_2',
            'apply_late_fee_automatically' => $data['apply_late_fee_automatically'] ?? true,
            'is_active' => true,
        ]);

        return ApiResponse::success($setting, 'Configuración económica guardada correctamente.', 201);
    }
}
