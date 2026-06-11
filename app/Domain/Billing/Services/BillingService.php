<?php

namespace App\Domain\Billing\Services;

use App\Models\BillingConcept;
use App\Models\Condominium;
use App\Models\CondominiumBillingSetting;
use App\Models\ExtraordinaryFee;
use App\Models\MonthlyFee;
use App\Models\Payment;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function generateMonthlyFees(Condominium $condominium, int $year, int $month): Collection
    {
        $setting = CondominiumBillingSetting::where('condominium_id', $condominium->id)->where('is_active', true)->first();
        $dueDay = min($setting?->due_day ?? 10, CarbonImmutable::create($year, $month)->daysInMonth);
        $dueDate = CarbonImmutable::create($year, $month, $dueDay)->toDateString();
        $concept = BillingConcept::where('code', 'alicuota_mensual')->firstOrFail();
        $extraConcept = BillingConcept::where('code', 'cuota_extraordinaria')->firstOrFail();

        return DB::transaction(function () use ($condominium, $year, $month, $dueDate, $concept, $extraConcept): Collection {
            return $condominium->units()
                ->whereNull('parent_unit_id')
                ->where('is_active', true)
                ->get()
                ->map(function (Unit $unit) use ($condominium, $year, $month, $dueDate, $concept, $extraConcept): MonthlyFee {
                    $responsibleId = DB::table('unit_user')
                        ->where('unit_id', $unit->id)
                        ->where('is_billing_responsible', true)
                        ->where('is_active', true)
                        ->whereNull('deleted_at')
                        ->value('user_id');

                    $aliquot = $unit->aliquots()
                        ->where('period_year', $year)
                        ->where('period_month', $month)
                        ->first();

                    $amount = (float) ($aliquot?->amount ?? 0);

                    $fee = MonthlyFee::updateOrCreate([
                        'unit_id' => $unit->id,
                        'period_year' => $year,
                        'period_month' => $month,
                    ], [
                        'condominium_id' => $condominium->id,
                        'billing_responsible_user_id' => $responsibleId,
                        'due_date' => $dueDate,
                        'total_amount' => $amount,
                        'paid_amount' => 0,
                        'balance_amount' => $amount,
                        'status' => $amount > 0 ? 'pending' : 'paid',
                    ]);

                    $fee->items()->where('billing_concept_id', $concept->id)->delete();
                    if ($amount > 0) {
                        $fee->items()->create([
                            'billing_concept_id' => $concept->id,
                            'description' => "Alícuota mensual {$year}-".str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                            'amount' => $amount,
                        ]);
                    }

                    $periodDate = CarbonImmutable::create($year, $month, 1);
                    $extraordinaryFees = $condominium->hasMany(ExtraordinaryFee::class)
                        ->where('is_active', true)
                        ->whereDate('starts_on', '<=', $periodDate->endOfMonth()->toDateString())
                        ->whereDate('ends_on', '>=', $periodDate->startOfMonth()->toDateString())
                        ->get()
                        ->filter(fn ($extra) => $extra->apply_to === 'all_units' || $extra->units()->whereKey($unit->id)->exists());

                    foreach ($extraordinaryFees as $extra) {
                        $exists = $fee->items()->where('billing_concept_id', $extraConcept->id)->where('description', $extra->name)->exists();
                        if (! $exists) {
                            $fee->items()->create([
                                'billing_concept_id' => $extraConcept->id,
                                'description' => $extra->name,
                                'amount' => $extra->amount,
                            ]);
                        }
                    }

                    $total = (float) $fee->items()->sum('amount');
                    $fee->update([
                        'total_amount' => $total,
                        'balance_amount' => max($total - (float) $fee->paid_amount, 0),
                        'status' => $total <= (float) $fee->paid_amount ? 'paid' : 'pending',
                    ]);

                    return $fee->fresh('items.billingConcept', 'unit');
                });
        });
    }

    public function allocatePayment(Payment $payment, array $monthlyFeeIds = []): Payment
    {
        return DB::transaction(function () use ($payment, $monthlyFeeIds): Payment {
            $remaining = (float) $payment->amount;
            $query = MonthlyFee::query()
                ->where('unit_id', $payment->unit_id)
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->orderBy('period_year')
                ->orderBy('period_month')
                ->lockForUpdate();

            if ($monthlyFeeIds !== []) {
                $query->whereIn('id', $monthlyFeeIds);
            }

            foreach ($query->get() as $fee) {
                if ($remaining <= 0) {
                    break;
                }

                $allocationAmount = min($remaining, (float) $fee->balance_amount);
                if ($allocationAmount <= 0) {
                    continue;
                }

                $payment->allocations()->create([
                    'monthly_fee_id' => $fee->id,
                    'amount' => $allocationAmount,
                ]);

                $paid = (float) $fee->paid_amount + $allocationAmount;
                $balance = max((float) $fee->total_amount - $paid, 0);
                $fee->update([
                    'paid_amount' => $paid,
                    'balance_amount' => $balance,
                    'status' => $balance <= 0 ? 'paid' : 'partial',
                ]);

                $remaining -= $allocationAmount;
            }

            $currentBalance = (float) DB::table('unit_account_movements')
                ->where('unit_id', $payment->unit_id)
                ->orderByDesc('id')
                ->value('balance_after');

            $balanceAfter = $currentBalance + max($remaining, 0);
            DB::table('unit_account_movements')->insert([
                'condominium_id' => $payment->condominium_id,
                'unit_id' => $payment->unit_id,
                'payment_id' => $payment->id,
                'monthly_fee_id' => null,
                'type' => $remaining > 0 ? 'credit' : 'payment',
                'amount' => $payment->amount,
                'balance_after' => $balanceAfter,
                'description' => $remaining > 0 ? 'Pago con saldo a favor.' : 'Pago aplicado a cuotas.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $payment->fresh('allocations.monthlyFee');
        });
    }
}
