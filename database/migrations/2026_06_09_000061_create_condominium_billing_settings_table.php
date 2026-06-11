<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condominium_billing_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->unsignedTinyInteger('due_day')->default(10);
            $table->unsignedSmallInteger('grace_days')->default(5);
            $table->string('late_fee_type')->default('percentage');
            $table->decimal('late_fee_value', 12, 4)->default(0);
            $table->string('late_fee_frequency')->default('monthly');
            $table->boolean('apply_late_fee_automatically')->default(true);
            $table->string('currency', 3)->default('USD');
            $table->string('rounding_mode')->default('round_2');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['condominium_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condominium_billing_settings');
    }
};
