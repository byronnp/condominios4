<?php

namespace Database\Seeders;

use App\Models\Condominium;
use App\Models\City;
use App\Models\Country;
use App\Models\Province;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CondominiumSeeder extends Seeder
{
    public function run(): void
    {
        $country = Country::where('code', 'EC')->first();
        $province = Province::where('code', 'EC-P')->first();
        $city = City::where('code', 'EC-P-QUITO')->first();

        $condominium = Condominium::updateOrCreate([
            'slug' => 'condominio-los-cedros',
        ], [
            'name' => 'Condominio Los Cedros',
            'ruc' => '1799999999001',
            'email' => 'administracion@loscedros.ec',
            'phone' => '0999999999',
            'address' => 'Av. Amazonas N34-120 y Atahualpa',
            'country_code' => $country?->code ?? 'EC',
            'province_id' => $province?->id,
            'city_id' => $city?->id,
            'total_units' => 24,
            'is_active' => true,
        ]);

        User::query()
            ->whereIn('email', ['byron_np@hotmail.com', 'byronnp@gmail.com', 'swagger.admin@example.com'])
            ->get()
            ->each(function (User $admin) use ($condominium): void {
                $condominium->users()->syncWithoutDetaching([
                    $admin->id => [
                        'is_active' => true,
                        'joined_at' => now(),
                    ],
                ]);
            });

        Condominium::query()
            ->whereNull('slug')
            ->get()
            ->each(fn (Condominium $item) => $item->update(['slug' => Str::slug($item->name)]));
    }
}
