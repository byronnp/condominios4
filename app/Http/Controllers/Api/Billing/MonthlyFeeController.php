<?php

namespace App\Http\Controllers\Api\Billing;

use App\Domain\Billing\Services\BillingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Billing\MonthlyFeeGenerateRequest;
use App\Models\Condominium;
use App\Models\MonthlyFee;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class MonthlyFeeController extends Controller
{
    public function __construct(private readonly BillingService $billingService) {}

    #[OA\Get(path: '/api/condominiums/{condominium}/monthly-fees', operationId: 'monthlyFeesIndex', summary: 'Listar cuotas mensuales', tags: ['Economía'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Cuotas encontradas')])]
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            MonthlyFee::where('condominium_id', $condominium->id)->with(['unit', 'billingResponsible', 'items.billingConcept'])->latest()->get(),
            'Cuotas mensuales encontradas.'
        );
    }

    #[OA\Post(path: '/api/condominiums/{condominium}/monthly-fees/generate', operationId: 'monthlyFeesGenerate', summary: 'Generar cuotas mensuales', tags: ['Economía'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Cuotas generadas')])]
    public function generate(MonthlyFeeGenerateRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $fees = $this->billingService->generateMonthlyFees($condominium, (int) $data['period_year'], (int) $data['period_month']);

        return ApiResponse::success($fees, 'Cuotas mensuales generadas correctamente.', 201);
    }
}
