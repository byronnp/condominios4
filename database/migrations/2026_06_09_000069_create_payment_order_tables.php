<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending')->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payment_gateway_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway');
            $table->string('gateway_transaction_id')->nullable();
            $table->string('gateway_status');
            $table->decimal('amount', 12, 2);
            $table->string('authorization_code')->nullable();
            $table->string('reference')->nullable();
            $table->string('voucher_number')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_transactions');
        Schema::dropIfExists('payment_orders');
    }
};
