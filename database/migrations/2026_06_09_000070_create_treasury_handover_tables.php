<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasury_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->foreignId('condominium_board_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->date('period_starts_on');
            $table->date('period_ends_on')->nullable();
            $table->foreignId('delivered_by_user_id')->nullable()->constrained('users', indexName: 'treasury_delivered_user_fk')->nullOnDelete();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users', indexName: 'treasury_received_user_fk')->nullOnDelete();
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('income_total', 12, 2)->default(0);
            $table->decimal('expense_total', 12, 2)->default(0);
            $table->decimal('system_balance', 12, 2)->default(0);
            $table->decimal('bank_balance', 12, 2)->default(0);
            $table->decimal('cash_balance', 12, 2)->default(0);
            $table->decimal('delivered_amount', 12, 2)->default(0);
            $table->decimal('received_amount', 12, 2)->default(0);
            $table->decimal('difference_amount', 12, 2)->default(0);
            $table->date('handover_date');
            $table->string('status')->default('draft')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('treasury_handover_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_handover_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users', indexName: 'treasury_attach_user_fk')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_handover_attachments');
        Schema::dropIfExists('treasury_handovers');
    }
};
