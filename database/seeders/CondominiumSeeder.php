<?php

namespace Database\Seeders;

use App\Models\CatalogItem;
use App\Models\City;
use App\Models\Condominium;
use App\Models\CondominiumFeature;
use App\Models\Country;
use App\Models\Province;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CondominiumSeeder extends Seeder
{
    public function run(): void
    {
        $country = Country::where('code', 'EC')->first();
        $province = Province::where('code', 'EC-P')->first();
        $city = City::where('code', 'EC-P-QUITO')->first();
        $condominiumType = CatalogItem::whereHas('catalog', fn ($query) => $query->where('code', 'condominium_types'))
            ->where('code', 'residencial')
            ->first();

        $condominium = Condominium::updateOrCreate([
            'slug' => 'condominio-los-cedros',
        ], [
            'name' => 'Condominio Los Cedros',
            'ruc' => '1799999999001',
            'condominium_type_id' => $condominiumType?->id,
            'description' => 'Condominio residencial con áreas comunes, seguridad privada y administración económica activa.',
            'email' => 'administracion@loscedros.ec',
            'phone' => '0999999999',
            'address' => 'Av. Amazonas N34-120 y Atahualpa',
            'address_reference' => 'A una cuadra del parque La Carolina.',
            'country_code' => $country?->code ?? 'EC',
            'province_id' => $province?->id,
            'city_id' => $city?->id,
            'latitude' => -0.180653,
            'longitude' => -78.467834,
            'towers_count' => 2,
            'houses_count' => 24,
            'logo_path' => null,
            'total_units' => 24,
            'is_active' => true,
        ]);

        $this->syncFeatures($condominium);

        foreach ($this->testCondominiums() as $index => $data) {
            $testCondominium = Condominium::updateOrCreate([
                'slug' => $data['slug'],
            ], [
                'name' => $data['name'],
                'ruc' => $data['ruc'],
                'condominium_type_id' => $condominiumType?->id,
                'description' => 'Condominio de prueba con administradores, casas y propietarios precargados.',
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'address_reference' => $data['address_reference'],
                'country_code' => $country?->code ?? 'EC',
                'province_id' => $province?->id,
                'city_id' => $city?->id,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'towers_count' => 0,
                'houses_count' => 5,
                'logo_path' => null,
                'total_units' => 5,
                'is_active' => true,
            ]);

            $this->syncFeatures($testCondominium);
            $this->seedAdministrators($testCondominium, $index + 1);
        }

        User::query()
            ->whereIn('email', ['byronnp@gmail.com', 'swagger.admin@example.com'])
            ->get()
            ->each(function (User $admin) use ($condominium): void {
                $condominium->users()->syncWithoutDetaching([
                    $admin->id => [
                        'is_active' => true,
                        'joined_at' => now(),
                    ],
                ]);
            });

        $seniorAdmin = User::where('email', 'byron_np@hotmail.com')->first();

        if ($seniorAdmin !== null) {
            $condominiumUserIds = DB::table('condominium_user')
                ->where('condominium_id', $condominium->id)
                ->where('user_id', $seniorAdmin->id)
                ->pluck('id');

            DB::table('condominium_user_role')
                ->whereIn('condominium_user_id', $condominiumUserIds)
                ->delete();

            DB::table('condominium_user')
                ->whereIn('id', $condominiumUserIds)
                ->delete();
        }

        Condominium::query()
            ->whereNull('slug')
            ->get()
            ->each(fn (Condominium $item) => $item->update(['slug' => Str::slug($item->name)]));
    }

    private function seedAdministrators(Condominium $condominium, int $condominiumNumber): void
    {
        $documentTypeId = CatalogItem::whereHas('catalog', fn ($query) => $query->where('code', 'document_types'))
            ->where('code', 'cedula')
            ->value('id');

        for ($administratorNumber = 1; $administratorNumber <= 3; $administratorNumber++) {
            $administrator = User::updateOrCreate([
                'email' => "admin.{$condominiumNumber}.{$administratorNumber}@condominios.test",
            ], [
                'name' => "ADMINISTRADOR {$administratorNumber} CONDOMINIO {$condominiumNumber}",
                'first_name' => "ADMINISTRADOR {$administratorNumber}",
                'last_name' => "CONDOMINIO {$condominiumNumber}",
                'password' => 'AdminTest123!',
                'country' => 'EC',
                'document_type_id' => $documentTypeId,
                'document_number' => sprintf('179%03d%04d', $condominiumNumber, $administratorNumber),
                'phone' => sprintf('098%07d', ($condominiumNumber * 10) + $administratorNumber),
                'secondary_phone' => null,
                'is_access_enabled' => true,
            ]);

            $condominium->users()->syncWithoutDetaching([
                $administrator->id => [
                    'is_active' => true,
                    'joined_at' => now(),
                    'deleted_at' => null,
                ],
            ]);
        }
    }

    private function testCondominiums(): array
    {
        return [
            [
                'slug' => 'condominio-jardines-del-valle',
                'name' => 'Condominio Jardines del Valle',
                'ruc' => '1799999999002',
                'email' => 'administracion@jardinesdelvalle.test',
                'phone' => '022900001',
                'address' => 'Av. Ilaló y Río Zamora',
                'address_reference' => 'Sector Valle de Los Chillos.',
                'latitude' => -0.2851,
                'longitude' => -78.4552,
            ],
            [
                'slug' => 'condominio-altos-del-bosque',
                'name' => 'Condominio Altos del Bosque',
                'ruc' => '1799999999003',
                'email' => 'administracion@altosdelbosque.test',
                'phone' => '022900002',
                'address' => 'Av. Occidental y Machala',
                'address_reference' => 'Junto al parque del sector.',
                'latitude' => -0.1458,
                'longitude' => -78.5036,
            ],
            [
                'slug' => 'condominio-villas-del-sol',
                'name' => 'Condominio Villas del Sol',
                'ruc' => '1799999999004',
                'email' => 'administracion@villasdelsol.test',
                'phone' => '022900003',
                'address' => 'Av. Simón Bolívar y Ruta Viva',
                'address_reference' => 'Ingreso por la vía principal.',
                'latitude' => -0.2094,
                'longitude' => -78.4217,
            ],
        ];
    }

    private function syncFeatures(Condominium $condominium): void
    {
        CatalogItem::whereHas('catalog', fn ($query) => $query->where('code', 'condominium_features'))
            ->whereIn('code', ['seguridad_24_7', 'camaras_seguridad', 'control_acceso', 'parqueadero_visitas'])
            ->get()
            ->each(function (CatalogItem $feature) use ($condominium): void {
                $condominiumFeature = CondominiumFeature::withTrashed()->firstOrNew([
                    'condominium_id' => $condominium->id,
                    'catalog_item_id' => $feature->id,
                ]);

                if ($condominiumFeature->exists && $condominiumFeature->trashed()) {
                    $condominiumFeature->restore();

                    return;
                }

                $condominiumFeature->save();
            });
    }
}
