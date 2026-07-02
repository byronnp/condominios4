<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('common_areas', function (Blueprint $table): void {
            $table->boolean('is_reservable')->default(true)->after('reservation_fee')->index();
        });
    }

    public function down(): void
    {
        Schema::table('common_areas', function (Blueprint $table): void {
            $table->dropColumn('is_reservable');
        });
    }
};
