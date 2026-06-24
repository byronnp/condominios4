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

    private function syncFeatures(Condominium $condominium): void
    {
        CatalogItem::whereHas('catalog', fn ($query) => $query->where('code', 'condominium_features'))
            ->whereIn('code', ['seguridad_24_7', 'camaras_seguridad', 'areas_verdes', 'piscina', 'gimnasio', 'salon_comunal'])
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
