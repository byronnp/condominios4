<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_access_invitations', function (Blueprint $table): void {
            $table->foreignId('condominium_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_access_invitations', function (Blueprint $table): void {
            $table->foreignId('condominium_id')->nullable(false)->change();
        });
    }
};
