<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_access_invitations', function (Blueprint $table): void {
            $table->foreignId('unit_id')->nullable()->change();
            $table->foreignId('role_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending')->after('token_hash')->index();
            $table->timestamp('revoked_at')->nullable()->after('accepted_at');
            $table->string('token_hash')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_access_invitations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['status', 'revoked_at']);
            $table->string('token_hash')->nullable(false)->change();
            $table->foreignId('unit_id')->nullable(false)->change();
        });
    }
};
