<?php

namespace Tests\Feature\Api\Billing;

use App\Models\BankReconciliation;
use App\Models\Condominium;
use App\Models\CondominiumPaymentMethod;
use App\Models\MonthlyFee;
use App\Models\Unit;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPhaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('La extensión pdo_sqlite es requerida para pruebas con SQLite en memoria.');
        }

        parent::setUp();

        config([
            'jwt.secret' => 'testing-secret-with-at-least-32-chars',
            'jwt.access_ttl_minutes' => 60,
            'jwt.refresh_ttl_days' => 30,
        ]);

        $this->seed(DatabaseSeeder::class);
    }

    public function test_phase_six_seeders_create_economic_bank_expense_and_treasury_data(): void
    {
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();

        $this->assertDatabaseHas('billing_concepts', ['code' => 'alicuota_mensual']);
        $this->assertDatabaseHas('condominium_billing_settings', ['condominium_id' => $condominium->id, 'late_fee_frequency' => 'monthly']);
        $this->assertDatabaseHas('monthly_fees', ['condominium_id' => $condominium->id, 'period_year' => 2026, 'period_month' => 6]);
        $this->assertDatabaseHas('payments', ['voucher_number' => 'COMP-0001', 'status' => 'confirmed']);
        $this->assertDatabaseHas('condominium_account_opening_balances', ['opening_balance' => 1500.00]);
        $this->assertDatabaseHas('bank_account_movements', ['voucher_number' => 'INT-JUN-2026']);
        $this->assertDatabaseHas('expenses', ['voucher_number' => 'LUZ-JUN-2026']);
        $this->assertDatabaseHas('bank_statement_imports', ['original_file_name' => 'estado-bancario-junio-2026.csv']);
        $this->assertDatabaseHas('bank_reconciliations', ['status' => 'balanced']);
        $this->assertDatabaseHas('treasury_handovers', ['type' => 'reception', 'status' => 'accepted']);
    }

    public function test_admin_can_generate_monthly_fee_and_register_payment(): void
    {
        $token = $this->loginToken();
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $house = Unit::where('code', 'CASA-01')->firstOrFail();
        $paymentMethod = CondominiumPaymentMethod::where('condominium_id', $condominium->id)->where('is_default', true)->firstOrFail();

        $this->postJson("/api/condominiums/{$condominium->id}/monthly-fees/generate", [
            'period_year' => 2026,
            'period_month' => 7,
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $fee = MonthlyFee::where('unit_id', $house->id)
            ->where('period_year', 2026)
            ->where('period_month', 7)
            ->firstOrFail();

        $this->postJson("/api/condominiums/{$condominium->id}/payments", [
            'unit_id' => $house->id,
            'condominium_payment_method_id' => $paymentMethod->id,
            'amount' => 25.00,
            'paid_at' => '2026-07-05 10:00:00',
            'reference' => 'TRX-JUL-TEST',
            'voucher_number' => 'COMP-JUL-TEST',
            'monthly_fee_ids' => [$fee->id],
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['voucher_number' => 'COMP-JUL-TEST']);

        $this->assertDatabaseHas('payment_allocations', ['monthly_fee_id' => $fee->id]);
    }

    public function test_bank_import_reconciliation_and_treasury_calculation_work(): void
    {
        $token = $this->loginToken();
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $paymentMethod = CondominiumPaymentMethod::where('condominium_id', $condominium->id)->where('is_default', true)->firstOrFail();
        $systemBalance = BankReconciliation::where('condominium_id', $condominium->id)->firstOrFail()->system_balance;

        $this->postJson("/api/condominiums/{$condominium->id}/bank-statement-imports", [
            'condominium_payment_method_id' => $paymentMethod->id,
            'period_year' => 2026,
            'period_month' => 7,
            'original_file_name' => 'estado-julio.json',
            'rows' => [
                [
                    'transaction_date' => '2026-06-10',
                    'reference' => 'TRX-CASA-01-JUN',
                    'voucher_number' => 'COMP-0001',
                    'description' => 'Transferencia Casa 01',
                    'amount' => 70.00,
                    'direction' => 'credit',
                ],
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonFragment(['match_status' => 'matched']);

        $this->postJson("/api/condominiums/{$condominium->id}/bank-reconciliations", [
            'condominium_payment_method_id' => $paymentMethod->id,
            'period_year' => 2026,
            'period_month' => 6,
            'bank_statement_balance' => $systemBalance,
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'balanced');

        $this->postJson("/api/condominiums/{$condominium->id}/treasury-handovers/calculate", [
            'period_starts_on' => '2026-06-01',
            'period_ends_on' => '2026-06-30',
            'condominium_payment_method_id' => $paymentMethod->id,
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['opening_balance', 'income_total', 'expense_total', 'system_balance']]);
    }

    private function loginToken(): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'byron_np@hotmail.com',
            'password' => 'admin123',
        ])->assertOk();

        return $response->json('data.access_token');
    }
}
