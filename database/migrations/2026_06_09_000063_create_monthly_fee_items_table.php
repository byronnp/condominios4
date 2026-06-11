<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_fee_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_fee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('billing_concept_id')->constrained()->restrictOnDelete();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->boolean('is_late_fee')->default(false);
            $table->unsignedSmallInteger('source_period_year')->nullable();
            $table->unsignedTinyInteger('source_period_month')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_fee_items');
    }
};
