<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condominium_user_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_user_id')->constrained('condominium_user')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['condominium_user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condominium_user_role');
    }
};
