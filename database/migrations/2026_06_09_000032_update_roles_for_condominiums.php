<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            try {
                $table->dropUnique(['name']);
            } catch (\Throwable) {
                // The old index may not exist in fresh or driver-specific test databases.
            }
        });

        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'condominium_id')) {
                $table->foreignId('condominium_id')->nullable()->after('id')->constrained('condominiums')->nullOnDelete();
            }

            if (! Schema::hasColumn('roles', 'code')) {
                $table->string('code')->nullable()->after('name');
            }

            if (! Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable()->after('code');
            }

            if (! Schema::hasColumn('roles', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('description');
            }

            if (! Schema::hasColumn('roles', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_system')->index();
            }

            if (! Schema::hasColumn('roles', 'deleted_at')) {
                $table->softDeletes();
            }

            $table->unique(['condominium_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            try {
                $table->dropUnique(['condominium_id', 'code']);
            } catch (\Throwable) {
                //
            }
        });
    }
};
