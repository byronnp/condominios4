<?php

namespace Database\Seeders;

use App\Domain\Billing\Services\BankingService;
use App\Domain\Billing\Services\BillingService;
use App\Models\BankAccountMovement;
use App\Models\BankReconciliation;
use App\Models\BankStatementImport;
use App\Models\BillingConcept;
use App\Models\Condominium;
use App\Models\CondominiumAccountOpeningBalance;
use App\Models\CondominiumBillingSetting;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExtraordinaryFee;
use App\Models\MonthlyFee;
use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\TreasuryHandover;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class BillingSeeder extends Seeder
{
    public function run(): void
    {
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->first();
        $admin = User::where('email', 'byron_np@hotmail.com')->first();
        $house = Unit::where('code', 'CASA-01')->first();
        $paymentMethod = $condominium?->paymentMethods()->where('is_default', true)->first();

        if (! $condominium || ! $admin || ! $house || ! $paymentMethod) {
            return;
        }

        $concepts = collect($this->concepts())->mapWithKeys(function (array $data): array {
            $concept = BillingConcept::updateOrCreate(['code' => $data['code']], [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_system' => true,
                'is_active' => true,
            ]);

            return [$concept->code => $concept];
        });

        CondominiumBillingSetting::updateOrCreate([
            'condominium_id' => $condominium->id,
            'is_active' => true,
        ], [
            'due_day' => 10,
            'grace_days' => 5,
            'late_fee_type' => 'percentage',
            'late_fee_value' => 2.00,
            'late_fee_frequency' => 'monthly',
            'apply_late_fee_automatically' => true,
            'currency' => 'USD',
            'rounding_mode' => 'round_2',
        ]);

        ExtraordinaryFee::updateOrCreate([
            'condominium_id' => $condominium->id,
            'name' => 'Reparación bomba de agua',
        ], [
            'billing_concept_id' => $concepts->get('cuota_extraordinaria')->id,
            'description' => 'Cuota extraordinaria de prueba aprobada por directiva.',
            'amount' => 20.00,
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-07-31',
            'apply_to' => 'all_units',
            'is_active' => true,
        ]);

        app(BillingService::class)->generateMonthlyFees($condominium, 2026, 6);

        $fee = $house->hasMany(MonthlyFee::class)->where('period_year', 2026)->where('period_month', 6)->first();

        $payment = Payment::updateOrCreate([
            'condominium_id' => $condominium->id,
            'unit_id' => $house->id,
            'voucher_number' => 'COMP-0001',
        ], [
            'user_id' => $admin->id,
            'condominium_payment_method_id' => $paymentMethod->id,
            'amount' => 70.00,
            'paid_at' => '2026-06-10 10:00:00',
            'reference' => 'TRX-CASA-01-JUN',
            'status' => 'confirmed',
            'notes' => 'Pago de prueba de alícuota y extraordinaria.',
        ]);

        if ($fee) {
            $payment->allocations()->delete();
            $fee->update([
                'paid_amount' => 0,
                'balance_amount' => $fee->total_amount,
                'status' => 'pending',
            ]);

            app(BillingService::class)->allocatePayment($payment, [$fee->id]);
        }

        CondominiumAccountOpeningBalance::updateOrCreate([
            'condominium_id' => $condominium->id,
            'condominium_payment_method_id' => $paymentMethod->id,
        ], [
            'opening_balance' => 1500.00,
            'opened_on' => '2026-06-01',
            'registered_by_user_id' => $admin->id,
            'notes' => 'Saldo inicial al comenzar uso del sistema.',
            'is_active' => true,
        ]);

        BankAccountMovement::updateOrCreate([
            'condominium_id' => $condominium->id,
            'condominium_payment_method_id' => $paymentMethod->id,
            'voucher_number' => 'INT-JUN-2026',
        ], [
            'type' => 'interest_income',
            'direction' => 'credit',
            'amount' => 3.25,
            'movement_date' => '2026-06-30',
            'reference' => 'INTERES-JUN',
            'description' => 'Interés generado por cuenta bancaria.',
            'registered_by_user_id' => $admin->id,
        ]);

        BankAccountMovement::updateOrCreate([
            'condominium_id' => $condominium->id,
            'condominium_payment_method_id' => $paymentMethod->id,
            'voucher_number' => 'COM-JUN-2026',
        ], [
            'type' => 'bank_fee',
            'direction' => 'debit',
            'amount' => 2.50,
            'movement_date' => '2026-06-30',
            'reference' => 'COMISION-JUN',
            'description' => 'Comisión bancaria mensual.',
            'registered_by_user_id' => $admin->id,
        ]);

        $category = ExpenseCategory::updateOrCreate([
            'condominium_id' => $condominium->id,
            'code' => 'luz',
        ], [
            'name' => 'Luz',
            'description' => 'Pago de energía eléctrica.',
            'is_active' => true,
        ]);

        Expense::updateOrCreate([
            'condominium_id' => $condominium->id,
            'voucher_number' => 'LUZ-JUN-2026',
        ], [
            'expense_category_id' => $category->id,
            'condominium_payment_method_id' => $paymentMethod->id,
            'supplier_name' => 'Empresa Eléctrica Quito',
            'supplier_document' => '1768152560001',
            'description' => 'Pago planilla de luz junio 2026.',
            'amount' => 120.50,
            'expense_date' => '2026-06-30',
            'paid_at' => '2026-06-30 15:00:00',
            'reference' => 'LUZ-JUN',
            'status' => 'paid',
            'registered_by_user_id' => $admin->id,
        ]);

        $import = BankStatementImport::updateOrCreate([
            'condominium_id' => $condominium->id,
            'condominium_payment_method_id' => $paymentMethod->id,
            'period_year' => 2026,
            'period_month' => 6,
        ], [
            'uploaded_by_user_id' => $admin->id,
            'original_file_name' => 'estado-bancario-junio-2026.csv',
            'file_path' => null,
            'status' => 'processed',
            'total_rows' => 3,
            'matched_rows' => 3,
            'unmatched_rows' => 0,
            'difference_rows' => 0,
        ]);

        $import->rows()->updateOrCreate([
            'voucher_number' => 'COMP-0001',
        ], [
            'transaction_date' => '2026-06-10',
            'reference' => 'TRX-CASA-01-JUN',
            'description' => 'Transferencia Casa 01',
            'amount' => 70.00,
            'direction' => 'credit',
            'matched_type' => 'payment',
            'matched_id' => $payment->id,
            'match_status' => 'matched',
            'difference_amount' => 0,
        ]);

        $systemBalance = app(BankingService::class)->systemBalance($condominium, $paymentMethod->id, 2026, 6);
        BankReconciliation::updateOrCreate([
            'condominium_id' => $condominium->id,
            'condominium_payment_method_id' => $paymentMethod->id,
            'period_year' => 2026,
            'period_month' => 6,
        ], [
            'bank_statement_import_id' => $import->id,
            'bank_statement_balance' => $systemBalance,
            'system_balance' => $systemBalance,
            'difference_amount' => 0,
            'status' => 'balanced',
            'reconciled_by_user_id' => $admin->id,
            'reconciled_at' => now(),
            'notes' => 'Conciliación de prueba cuadrada.',
        ]);

        PaymentOrder::updateOrCreate([
            'condominium_id' => $condominium->id,
            'unit_id' => $house->id,
            'user_id' => $admin->id,
        ], [
            'amount' => 70.00,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        TreasuryHandover::updateOrCreate([
            'condominium_id' => $condominium->id,
            'type' => 'reception',
            'period_starts_on' => '2026-06-01',
        ], [
            'condominium_board_id' => $condominium->boards()->where('is_active', true)->value('id'),
            'delivered_by_user_id' => null,
            'received_by_user_id' => $admin->id,
            'opening_balance' => 1500.00,
            'income_total' => 0,
            'expense_total' => 0,
            'system_balance' => 1500.00,
            'bank_balance' => 1500.00,
            'cash_balance' => 0,
            'delivered_amount' => 0,
            'received_amount' => 1500.00,
            'difference_amount' => 0,
            'handover_date' => '2026-06-01',
            'status' => 'accepted',
            'notes' => 'Recepción inicial de tesorería.',
        ]);
    }

    private function concepts(): array
    {
        return [
            ['code' => 'alicuota_mensual', 'name' => 'Alícuota mensual'],
            ['code' => 'cuota_extraordinaria', 'name' => 'Cuota extraordinaria'],
            ['code' => 'interes_mora', 'name' => 'Interés por mora'],
            ['code' => 'multa', 'name' => 'Multa'],
            ['code' => 'ajuste', 'name' => 'Ajuste'],
            ['code' => 'reserva_area_comun', 'name' => 'Reserva de área común'],
        ];
    }
}
