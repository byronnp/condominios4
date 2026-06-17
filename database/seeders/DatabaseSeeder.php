<?php

namespace Database\Seeders;

use App\Models\Catalog;
use App\Models\Tenant;
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

        $tenant = Tenant::firstOrCreate([
            'slug' => 'admin-platform',
        ], [
            'name' => 'Admin Platform',
        ]);

        $seniorAdmin = User::updateOrCreate([
            'email' => 'byron_np@hotmail.com',
        ], [
            'name' => 'ADMINISTRADOR SENIOR',
            'password' => 'admin123',
            'country' => 'EC',
            'document_type_id' => $documentType->id,
            'document_number' => '1716128911',
            'phone' => '0999999999',
            'secondary_phone' => null,
            'is_access_enabled' => true,
        ]);

        $seniorAdmin->tenants()->syncWithoutDetaching([$tenant->id]);
        $seniorAdmin->assignRole('admin', $tenant);

        User::updateOrCreate([
            'email' => 'byronnp@gmail.com',
        ], [
            'name' => 'ADMINISTRADOR CONDOMINIO',
            'password' => 'admin123',
            'country' => 'EC',
            'document_type_id' => $documentType->id,
            'document_number' => '1716128912',
            'phone' => '0999999997',
            'secondary_phone' => null,
            'is_access_enabled' => true,
        ]);

        User::query()
            ->where('email', 'byronnp@gmail.com')
            ->first()
            ?->tenants()
            ->syncWithoutDetaching([$tenant->id]);

        User::updateOrCreate([
            'email' => 'swagger.admin@example.com',
        ], [
            'name' => 'SWAGGER ADMIN',
            'password' => 'Swagger123!',
            'country' => 'EC',
            'document_type_id' => $documentType->id,
            'document_number' => '1799999999',
            'phone' => '0999999998',
            'secondary_phone' => null,
            'is_access_enabled' => true,
        ]);

        User::query()
            ->where('email', 'swagger.admin@example.com')
            ->first()
            ?->tenants()
            ->syncWithoutDetaching([$tenant->id]);

        $this->call([
            CondominiumSeeder::class,
            RolePermissionSeeder::class,
            MenuSeeder::class,
            BoardSeeder::class,
            CondominiumPaymentMethodSeeder::class,
            UnitSeeder::class,
            BillingSeeder::class,
            OperationsSeeder::class,
        ]);
    }
}
