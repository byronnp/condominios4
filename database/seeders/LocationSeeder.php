<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->locations() as $countryData) {
            $country = Country::updateOrCreate([
                'code' => $countryData['code'],
            ], [
                'iso3' => $countryData['iso3'],
                'name' => $countryData['name'],
                'phone_code' => $countryData['phone_code'],
                'currency_code' => $countryData['currency_code'],
                'is_active' => true,
            ]);

            foreach ($countryData['provinces'] as $provinceData) {
                $province = $country->provinces()->updateOrCreate([
                    'code' => $provinceData['code'],
                ], [
                    'name' => $provinceData['name'],
                    'is_active' => true,
                ]);

                foreach ($provinceData['cities'] as $cityData) {
                    $province->cities()->updateOrCreate([
                        'code' => $cityData['code'],
                    ], [
                        'name' => $cityData['name'],
                        'is_active' => true,
                    ]);
                }
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function locations(): array
    {
        return [
            [
                'code' => 'EC',
                'iso3' => 'ECU',
                'name' => 'Ecuador',
                'phone_code' => '+593',
                'currency_code' => 'USD',
                'provinces' => [
                    ['code' => 'EC-A', 'name' => 'Azuay', 'cities' => [
                        ['code' => 'EC-A-CUENCA', 'name' => 'Cuenca'],
                        ['code' => 'EC-A-GUALACEO', 'name' => 'Gualaceo'],
                        ['code' => 'EC-A-PAUTE', 'name' => 'Paute'],
                    ]],
                    ['code' => 'EC-B', 'name' => 'Bolívar', 'cities' => [
                        ['code' => 'EC-B-GUARANDA', 'name' => 'Guaranda'],
                        ['code' => 'EC-B-SAN-MIGUEL', 'name' => 'San Miguel'],
                    ]],
                    ['code' => 'EC-F', 'name' => 'Cañar', 'cities' => [
                        ['code' => 'EC-F-AZOGUES', 'name' => 'Azogues'],
                        ['code' => 'EC-F-CANAR', 'name' => 'Cañar'],
                        ['code' => 'EC-F-LA-TRONCAL', 'name' => 'La Troncal'],
                    ]],
                    ['code' => 'EC-C', 'name' => 'Carchi', 'cities' => [
                        ['code' => 'EC-C-TULCAN', 'name' => 'Tulcán'],
                        ['code' => 'EC-C-MIRA', 'name' => 'Mira'],
                    ]],
                    ['code' => 'EC-H', 'name' => 'Chimborazo', 'cities' => [
                        ['code' => 'EC-H-RIOBAMBA', 'name' => 'Riobamba'],
                        ['code' => 'EC-H-ALAUSI', 'name' => 'Alausí'],
                        ['code' => 'EC-H-GUANO', 'name' => 'Guano'],
                    ]],
                    ['code' => 'EC-X', 'name' => 'Cotopaxi', 'cities' => [
                        ['code' => 'EC-X-LATACUNGA', 'name' => 'Latacunga'],
                        ['code' => 'EC-X-PUJILI', 'name' => 'Pujilí'],
                        ['code' => 'EC-X-SALCEDO', 'name' => 'Salcedo'],
                    ]],
                    ['code' => 'EC-O', 'name' => 'El Oro', 'cities' => [
                        ['code' => 'EC-O-MACHALA', 'name' => 'Machala'],
                        ['code' => 'EC-O-PASAJE', 'name' => 'Pasaje'],
                        ['code' => 'EC-O-SANTA-ROSA', 'name' => 'Santa Rosa'],
                    ]],
                    ['code' => 'EC-E', 'name' => 'Esmeraldas', 'cities' => [
                        ['code' => 'EC-E-ESMERALDAS', 'name' => 'Esmeraldas'],
                        ['code' => 'EC-E-ATACAMES', 'name' => 'Atacames'],
                        ['code' => 'EC-E-QUININDE', 'name' => 'Quinindé'],
                    ]],
                    ['code' => 'EC-W', 'name' => 'Galápagos', 'cities' => [
                        ['code' => 'EC-W-PUERTO-BAQUERIZO-MORENO', 'name' => 'Puerto Baquerizo Moreno'],
                        ['code' => 'EC-W-PUERTO-AYORA', 'name' => 'Puerto Ayora'],
                    ]],
                    ['code' => 'EC-G', 'name' => 'Guayas', 'cities' => [
                        ['code' => 'EC-G-GUAYAQUIL', 'name' => 'Guayaquil'],
                        ['code' => 'EC-G-DURAN', 'name' => 'Durán'],
                        ['code' => 'EC-G-DAULE', 'name' => 'Daule'],
                        ['code' => 'EC-G-SAMBORONDON', 'name' => 'Samborondón'],
                    ]],
                    ['code' => 'EC-I', 'name' => 'Imbabura', 'cities' => [
                        ['code' => 'EC-I-IBARRA', 'name' => 'Ibarra'],
                        ['code' => 'EC-I-OTAVALO', 'name' => 'Otavalo'],
                        ['code' => 'EC-I-COTACACHI', 'name' => 'Cotacachi'],
                    ]],
                    ['code' => 'EC-L', 'name' => 'Loja', 'cities' => [
                        ['code' => 'EC-L-LOJA', 'name' => 'Loja'],
                        ['code' => 'EC-L-CATAMAYO', 'name' => 'Catamayo'],
                        ['code' => 'EC-L-MACARA', 'name' => 'Macará'],
                    ]],
                    ['code' => 'EC-R', 'name' => 'Los Ríos', 'cities' => [
                        ['code' => 'EC-R-BABAHOYO', 'name' => 'Babahoyo'],
                        ['code' => 'EC-R-QUEVEDO', 'name' => 'Quevedo'],
                        ['code' => 'EC-R-VINCES', 'name' => 'Vinces'],
                    ]],
                    ['code' => 'EC-M', 'name' => 'Manabí', 'cities' => [
                        ['code' => 'EC-M-PORTOVIEJO', 'name' => 'Portoviejo'],
                        ['code' => 'EC-M-MANTA', 'name' => 'Manta'],
                        ['code' => 'EC-M-CHONE', 'name' => 'Chone'],
                    ]],
                    ['code' => 'EC-S', 'name' => 'Morona Santiago', 'cities' => [
                        ['code' => 'EC-S-MACAS', 'name' => 'Macas'],
                        ['code' => 'EC-S-SUCUA', 'name' => 'Sucúa'],
                    ]],
                    ['code' => 'EC-N', 'name' => 'Napo', 'cities' => [
                        ['code' => 'EC-N-TENA', 'name' => 'Tena'],
                        ['code' => 'EC-N-ARCHIDONA', 'name' => 'Archidona'],
                    ]],
                    ['code' => 'EC-D', 'name' => 'Orellana', 'cities' => [
                        ['code' => 'EC-D-COCA', 'name' => 'Puerto Francisco de Orellana'],
                        ['code' => 'EC-D-LORETO', 'name' => 'Loreto'],
                    ]],
                    ['code' => 'EC-Y', 'name' => 'Pastaza', 'cities' => [
                        ['code' => 'EC-Y-PUYO', 'name' => 'Puyo'],
                        ['code' => 'EC-Y-MERA', 'name' => 'Mera'],
                    ]],
                    ['code' => 'EC-P', 'name' => 'Pichincha', 'cities' => [
                        ['code' => 'EC-P-QUITO', 'name' => 'Quito'],
                        ['code' => 'EC-P-CAYAMBE', 'name' => 'Cayambe'],
                        ['code' => 'EC-P-MEJIA', 'name' => 'Machachi'],
                        ['code' => 'EC-P-RUMINAHUI', 'name' => 'Sangolquí'],
                    ]],
                    ['code' => 'EC-SE', 'name' => 'Santa Elena', 'cities' => [
                        ['code' => 'EC-SE-SANTA-ELENA', 'name' => 'Santa Elena'],
                        ['code' => 'EC-SE-LA-LIBERTAD', 'name' => 'La Libertad'],
                        ['code' => 'EC-SE-SALINAS', 'name' => 'Salinas'],
                    ]],
                    ['code' => 'EC-SD', 'name' => 'Santo Domingo de los Tsáchilas', 'cities' => [
                        ['code' => 'EC-SD-SANTO-DOMINGO', 'name' => 'Santo Domingo'],
                        ['code' => 'EC-SD-LA-CONCORDIA', 'name' => 'La Concordia'],
                    ]],
                    ['code' => 'EC-U', 'name' => 'Sucumbíos', 'cities' => [
                        ['code' => 'EC-U-NUEVA-LOJA', 'name' => 'Nueva Loja'],
                        ['code' => 'EC-U-SHUSHUFINDI', 'name' => 'Shushufindi'],
                    ]],
                    ['code' => 'EC-T', 'name' => 'Tungurahua', 'cities' => [
                        ['code' => 'EC-T-AMBATO', 'name' => 'Ambato'],
                        ['code' => 'EC-T-BANOS', 'name' => 'Baños de Agua Santa'],
                        ['code' => 'EC-T-PELILEO', 'name' => 'Pelileo'],
                    ]],
                    ['code' => 'EC-Z', 'name' => 'Zamora Chinchipe', 'cities' => [
                        ['code' => 'EC-Z-ZAMORA', 'name' => 'Zamora'],
                        ['code' => 'EC-Z-YANTZAZA', 'name' => 'Yantzaza'],
                    ]],
                ],
            ],
            [
                'code' => 'CO',
                'iso3' => 'COL',
                'name' => 'Colombia',
                'phone_code' => '+57',
                'currency_code' => 'COP',
                'provinces' => [
                    ['code' => 'CO-ANT', 'name' => 'Antioquia', 'cities' => [
                        ['code' => 'CO-ANT-MEDELLIN', 'name' => 'Medellín'],
                        ['code' => 'CO-ANT-BELLO', 'name' => 'Bello'],
                    ]],
                    ['code' => 'CO-CUN', 'name' => 'Cundinamarca', 'cities' => [
                        ['code' => 'CO-CUN-BOGOTA', 'name' => 'Bogotá'],
                        ['code' => 'CO-CUN-SOACHA', 'name' => 'Soacha'],
                    ]],
                ],
            ],
            [
                'code' => 'PE',
                'iso3' => 'PER',
                'name' => 'Perú',
                'phone_code' => '+51',
                'currency_code' => 'PEN',
                'provinces' => [
                    ['code' => 'PE-LIM', 'name' => 'Lima', 'cities' => [
                        ['code' => 'PE-LIM-LIMA', 'name' => 'Lima'],
                        ['code' => 'PE-LIM-CALLAO', 'name' => 'Callao'],
                    ]],
                    ['code' => 'PE-PIU', 'name' => 'Piura', 'cities' => [
                        ['code' => 'PE-PIU-PIURA', 'name' => 'Piura'],
                        ['code' => 'PE-PIU-SULLANA', 'name' => 'Sullana'],
                    ]],
                ],
            ],
        ];
    }
}
