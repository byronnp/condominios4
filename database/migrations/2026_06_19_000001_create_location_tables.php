<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('iso3', 3)->nullable()->unique();
            $table->string('name');
            $table->string('phone_code', 10)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'name']);
        });

        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['country_id', 'name']);
            $table->index(['country_id', 'is_active', 'name']);
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['province_id', 'name']);
            $table->index(['province_id', 'is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('countries');
    }
};
