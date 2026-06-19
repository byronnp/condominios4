<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('condominiums', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable()->after('address');
            $table->foreignId('province_id')->nullable()->after('country_code')->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('province_id')->constrained()->nullOnDelete();

            $table->foreign('country_code')->references('code')->on('countries')->nullOnDelete();
            $table->index(['country_code', 'province_id', 'city_id']);
        });

        if (Schema::hasColumn('condominiums', 'country')) {
            $defaultCountryExists = DB::table('countries')->where('code', 'EC')->exists();

            if ($defaultCountryExists) {
                DB::table('condominiums')
                    ->whereNull('country_code')
                    ->update(['country_code' => 'EC']);
            }
        }

        Schema::table('condominiums', function (Blueprint $table) {
            if (Schema::hasColumn('condominiums', 'city')) {
                $table->dropColumn('city');
            }

            if (Schema::hasColumn('condominiums', 'province')) {
                $table->dropColumn('province');
            }

            if (Schema::hasColumn('condominiums', 'country')) {
                $table->dropColumn('country');
            }
        });
    }

    public function down(): void
    {
        Schema::table('condominiums', function (Blueprint $table) {
            $table->string('city')->nullable()->after('address');
            $table->string('province')->nullable()->after('city');
            $table->string('country', 2)->default('EC')->after('province');
        });

        DB::table('condominiums')
            ->whereNotNull('country_code')
            ->update(['country' => DB::raw('country_code')]);

        Schema::table('condominiums', function (Blueprint $table) {
            $table->dropIndex(['country_code', 'province_id', 'city_id']);
            $table->dropForeign(['country_code']);
            $table->dropColumn('country_code');
            $table->dropConstrainedForeignId('city_id');
            $table->dropConstrainedForeignId('province_id');
        });
    }
};
