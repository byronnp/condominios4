<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('condominiums', function (Blueprint $table) {
            $table->foreignId('condominium_type_id')
                ->nullable()
                ->after('ruc')
                ->constrained('catalog_items')
                ->nullOnDelete();
            $table->text('description')->nullable()->after('condominium_type_id');
            $table->string('address_reference')->nullable()->after('address');
            $table->decimal('latitude', 10, 7)->nullable()->after('city_id');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedInteger('towers_count')->default(0)->after('longitude');
            $table->unsignedInteger('houses_count')->default(0)->after('towers_count');
            $table->string('logo_path')->nullable()->after('houses_count');
        });

        Schema::create('condominium_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['condominium_id', 'catalog_item_id']);
            $table->index(['condominium_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condominium_features');

        Schema::table('condominiums', function (Blueprint $table) {
            $table->dropConstrainedForeignId('condominium_type_id');
            $table->dropColumn([
                'description',
                'address_reference',
                'latitude',
                'longitude',
                'towers_count',
                'houses_count',
                'logo_path',
            ]);
        });
    }
};
