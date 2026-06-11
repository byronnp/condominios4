<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condominium_account_opening_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->foreignId('condominium_payment_method_id')->constrained('condominium_payment_methods', indexName: 'opening_cpm_fk')->cascadeOnDelete();
            $table->decimal('opening_balance', 12, 2);
            $table->date('opened_on');
            $table->foreignId('registered_by_user_id')->nullable()->constrained('users', indexName: 'opening_user_fk')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bank_account_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->foreignId('condominium_payment_method_id')->constrained('condominium_payment_methods', indexName: 'bank_movements_cpm_fk')->cascadeOnDelete();
            $table->string('type');
            $table->string('direction');
            $table->decimal('amount', 12, 2);
            $table->date('movement_date');
            $table->string('reference')->nullable();
            $table->string('voucher_number')->nullable();
            $table->text('description');
            $table->foreignId('registered_by_user_id')->nullable()->constrained('users', indexName: 'bank_movements_user_fk')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bank_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->foreignId('condominium_payment_method_id')->constrained('condominium_payment_methods', indexName: 'bank_imports_cpm_fk')->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users', indexName: 'bank_imports_user_fk')->nullOnDelete();
            $table->string('original_file_name');
            $table->string('file_path')->nullable();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->string('status')->default('uploaded')->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('matched_rows')->default(0);
            $table->unsignedInteger('unmatched_rows')->default(0);
            $table->unsignedInteger('difference_rows')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bank_statement_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_import_id')->constrained('bank_statement_imports', indexName: 'bank_rows_import_fk')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->string('reference')->nullable();
            $table->string('voucher_number')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('direction');
            $table->json('raw_data')->nullable();
            $table->string('matched_type')->nullable();
            $table->unsignedBigInteger('matched_id')->nullable();
            $table->string('match_status')->default('unmatched')->index();
            $table->decimal('difference_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->foreignId('condominium_payment_method_id')->constrained('condominium_payment_methods', indexName: 'bank_recons_cpm_fk')->cascadeOnDelete();
            $table->foreignId('bank_statement_import_id')->nullable()->constrained('bank_statement_imports', indexName: 'bank_recons_import_fk')->nullOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->decimal('bank_statement_balance', 12, 2);
            $table->decimal('system_balance', 12, 2);
            $table->decimal('difference_amount', 12, 2);
            $table->string('status')->default('draft')->index();
            $table->foreignId('reconciled_by_user_id')->nullable()->constrained('users', indexName: 'bank_recons_user_fk')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bank_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations', indexName: 'bank_recon_items_recon_fk')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bank_account_movement_id')->nullable()->constrained('bank_account_movements', indexName: 'bank_recon_items_movement_fk')->nullOnDelete();
            $table->foreignId('expense_id')->nullable();
            $table->date('transaction_date');
            $table->string('reference')->nullable();
            $table->string('voucher_number')->nullable();
            $table->text('description')->nullable();
            $table->decimal('bank_amount', 12, 2);
            $table->decimal('system_amount', 12, 2);
            $table->decimal('difference_amount', 12, 2)->default(0);
            $table->string('status')->default('matched');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_items');
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('bank_statement_import_rows');
        Schema::dropIfExists('bank_statement_imports');
        Schema::dropIfExists('bank_account_movements');
        Schema::dropIfExists('condominium_account_opening_balances');
    }
};
