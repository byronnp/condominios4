<?php

namespace App\Domain\Billing\Services;

use App\Models\Condominium;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class BankingService
{
    public function systemBalance(Condominium $condominium, int $paymentMethodId, ?int $year = null, ?int $month = null): float
    {
        $endDate = $year && $month
            ? CarbonImmutable::create($year, $month, 1)->endOfMonth()
            : now();

        $opening = (float) DB::table('condominium_account_opening_balances')
            ->where('condominium_id', $condominium->id)
            ->where('condominium_payment_method_id', $paymentMethodId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->sum('opening_balance');

        $payments = (float) DB::table('payments')
            ->where('condominium_id', $condominium->id)
            ->where('condominium_payment_method_id', $paymentMethodId)
            ->where('status', 'confirmed')
            ->whereDate('paid_at', '<=', $endDate->toDateString())
            ->whereNull('deleted_at')
            ->sum('amount');

        $credits = (float) DB::table('bank_account_movements')
            ->where('condominium_id', $condominium->id)
            ->where('condominium_payment_method_id', $paymentMethodId)
            ->where('direction', 'credit')
            ->whereDate('movement_date', '<=', $endDate->toDateString())
            ->whereNull('deleted_at')
            ->sum('amount');

        $debits = (float) DB::table('bank_account_movements')
            ->where('condominium_id', $condominium->id)
            ->where('condominium_payment_method_id', $paymentMethodId)
            ->where('direction', 'debit')
            ->whereDate('movement_date', '<=', $endDate->toDateString())
            ->whereNull('deleted_at')
            ->sum('amount');

        $expenses = (float) DB::table('expenses')
            ->where('condominium_id', $condominium->id)
            ->where('condominium_payment_method_id', $paymentMethodId)
            ->where('status', 'paid')
            ->whereDate('paid_at', '<=', $endDate->toDateString())
            ->whereNull('deleted_at')
            ->sum('amount');

        return round($opening + $payments + $credits - $debits - $expenses, 2);
    }
}
