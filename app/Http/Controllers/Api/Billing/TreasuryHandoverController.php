<?php

namespace App\Http\Controllers\Api\Billing;

use App\Domain\Billing\Services\BankingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Billing\TreasuryHandoverCalculateRequest;
use App\Http\Requests\Api\Billing\TreasuryHandoverStoreRequest;
use App\Http\Resources\Api\Billing\TreasuryHandoverResource;
use App\Models\Condominium;
use App\Models\TreasuryHandover;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TreasuryHandoverController extends Controller
{
    public function __construct(private readonly BankingService $bankingService) {}

    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(TreasuryHandoverResource::collection(TreasuryHandover::where('condominium_id', $condominium->id)->latest('handover_date')->get()), 'Registros de tesorería encontrados.');
    }

    public function calculate(TreasuryHandoverCalculateRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        return ApiResponse::success(new TreasuryHandoverResource($this->calculateValues($condominium, $data)), 'Valores de tesorería calculados.');
    }

    public function store(TreasuryHandoverStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $endsOn = $data['period_ends_on'] ?? $data['period_starts_on'];
        $values = $this->calculateValues($condominium, [
            ...$data,
            'period_ends_on' => $endsOn,
        ]);

        $deliveredAmount = (float) $data['bank_balance'] + (float) ($data['cash_balance'] ?? 0);
        $handover = TreasuryHandover::create([
            'condominium_id' => $condominium->id,
            'condominium_board_id' => $condominium->boards()->where('is_active', true)->value('id'),
            'type' => $data['type'],
            'period_starts_on' => $data['period_starts_on'],
            'period_ends_on' => $data['period_ends_on'] ?? null,
            'delivered_by_user_id' => $data['delivered_by_user_id'] ?? null,
            'received_by_user_id' => $data['received_by_user_id'] ?? $request->user()?->id,
            'opening_balance' => $values['opening_balance'],
            'income_total' => $values['income_total'],
            'expense_total' => $values['expense_total'],
            'system_balance' => $values['system_balance'],
            'bank_balance' => $data['bank_balance'],
            'cash_balance' => $data['cash_balance'] ?? 0,
            'delivered_amount' => $data['type'] === 'handover' ? $deliveredAmount : 0,
            'received_amount' => $data['type'] === 'reception' ? $deliveredAmount : 0,
            'difference_amount' => round($deliveredAmount - $values['system_balance'], 2),
            'handover_date' => now()->toDateString(),
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
        ]);

        return ApiResponse::success(new TreasuryHandoverResource($handover), 'Registro de tesorería creado correctamente.', 201);
    }

    private function calculateValues(Condominium $condominium, array $data): array
    {
        $opening = (float) DB::table('condominium_account_opening_balances')
            ->where('condominium_id', $condominium->id)
            ->where('condominium_payment_method_id', $data['condominium_payment_method_id'])
            ->whereNull('deleted_at')
            ->sum('opening_balance');

        $income = (float) DB::table('payments')
            ->where('condominium_id', $condominium->id)
            ->where('status', 'confirmed')
            ->whereBetween('paid_at', [$data['period_starts_on'], $data['period_ends_on']])
            ->whereNull('deleted_at')
            ->sum('amount');

        $bankCredit = (float) DB::table('bank_account_movements')
            ->where('condominium_id', $condominium->id)
            ->where('direction', 'credit')
            ->whereBetween('movement_date', [$data['period_starts_on'], $data['period_ends_on']])
            ->whereNull('deleted_at')
            ->sum('amount');

        $expenses = (float) DB::table('expenses')
            ->where('condominium_id', $condominium->id)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$data['period_starts_on'], $data['period_ends_on']])
            ->whereNull('deleted_at')
            ->sum('amount');

        $bankDebit = (float) DB::table('bank_account_movements')
            ->where('condominium_id', $condominium->id)
            ->where('direction', 'debit')
            ->whereBetween('movement_date', [$data['period_starts_on'], $data['period_ends_on']])
            ->whereNull('deleted_at')
            ->sum('amount');

        return [
            'opening_balance' => round($opening, 2),
            'income_total' => round($income + $bankCredit, 2),
            'expense_total' => round($expenses + $bankDebit, 2),
            'system_balance' => round($opening + $income + $bankCredit - $expenses - $bankDebit, 2),
        ];
    }
}
