<?php

namespace Database\Seeders;

use App\Models\Catalog;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CatalogSeeder::class);

        $documentType = Catalog::where('code', 'document_types')
            ->firstOrFail()
            ->items()
            ->where('code', 'cedula')
            ->firstOrFail();

        User::updateOrCreate([
            'email' => 'byron_np@hotmail.com',
        ], [
            'name' => 'BYRON VINICIO PILATAXI ALMACHI',
            'password' => 'admin123',
            'country' => 'EC',
            'document_type_id' => $documentType->id,
            'document_number' => '1716128911',
        ]);

        $this->call([
            CondominiumSeeder::class,
            RolePermissionSeeder::class,
            MenuSeeder::class,
            BoardSeeder::class,
            CondominiumPaymentMethodSeeder::class,
        ]);
    }
}
