<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extraordinary_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->foreignId('billing_concept_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('apply_to')->default('all_units');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('extraordinary_fee_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extraordinary_fee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['extraordinary_fee_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extraordinary_fee_units');
        Schema::dropIfExists('extraordinary_fees');
    }
};
