<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('document_number');
            }

            if (! Schema::hasColumn('users', 'secondary_phone')) {
                $table->string('secondary_phone')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('users', 'is_access_enabled')) {
                $table->boolean('is_access_enabled')->default(true)->after('secondary_phone')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }

            if (Schema::hasColumn('users', 'secondary_phone')) {
                $table->dropColumn('secondary_phone');
            }

            if (Schema::hasColumn('users', 'is_access_enabled')) {
                $table->dropColumn('is_access_enabled');
            }
        });
    }
};
