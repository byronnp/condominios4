<?php

namespace App\Http\Controllers\Api\Billing;

use App\Domain\Billing\Services\BankingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Billing\BankReconciliationStoreRequest;
use App\Http\Resources\Api\Billing\BankReconciliationResource;
use App\Models\BankReconciliation;
use App\Models\Condominium;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class BankReconciliationController extends Controller
{
    public function __construct(private readonly BankingService $bankingService) {}

    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(BankReconciliationResource::collection(BankReconciliation::where('condominium_id', $condominium->id)->with('items')->latest()->get()), 'Conciliaciones encontradas.');
    }

    public function store(BankReconciliationStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $systemBalance = $this->bankingService->systemBalance($condominium, (int) $data['condominium_payment_method_id'], (int) $data['period_year'], (int) $data['period_month']);
        $difference = round((float) $data['bank_statement_balance'] - $systemBalance, 2);

        $reconciliation = BankReconciliation::create([
            ...$data,
            'condominium_id' => $condominium->id,
            'system_balance' => $systemBalance,
            'difference_amount' => $difference,
            'status' => abs($difference) < 0.01 ? 'balanced' : 'difference',
            'reconciled_by_user_id' => $request->user()?->id,
            'reconciled_at' => now(),
        ]);

        return ApiResponse::success(new BankReconciliationResource($reconciliation), 'Conciliación generada correctamente.', 201);
    }
}
