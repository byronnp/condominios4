<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->string('category_code', 100)->nullable()->after('icon');
            $table->string('category_name', 150)->nullable()->after('category_code');
            $table->unsignedInteger('category_sort_order')->default(0)->after('category_name');

            $table->index(['category_code', 'category_sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropIndex(['category_code', 'category_sort_order']);
            $table->dropColumn(['category_code', 'category_name', 'category_sort_order']);
        });
    }
};
