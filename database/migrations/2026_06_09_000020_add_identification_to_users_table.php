<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('country', 2)->default('EC')->after('password');
            $table->foreignId('document_type_id')->nullable()->after('country')->constrained('catalog_items')->nullOnDelete();
            $table->string('document_number', 30)->nullable()->after('document_type_id');
            $table->softDeletes();

            $table->unique(['country', 'document_type_id', 'document_number']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['country', 'document_type_id', 'document_number']);
            $table->dropConstrainedForeignId('document_type_id');
            $table->dropColumn(['country', 'document_number']);
            $table->dropSoftDeletes();
        });
    }
};
