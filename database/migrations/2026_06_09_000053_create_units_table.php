<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->foreignId('condominium_block_id')->nullable()->constrained('condominium_blocks')->nullOnDelete();
            $table->foreignId('parent_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('unit_type_id')->constrained('catalog_items')->restrictOnDelete();
            $table->string('code');
            $table->string('number');
            $table->string('floor')->nullable();
            $table->decimal('area_m2', 10, 2)->nullable();
            $table->decimal('current_aliquot_percentage', 8, 4)->nullable();
            $table->boolean('is_assignable')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['condominium_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
