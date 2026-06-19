<?php

namespace Tests\Feature\Api\Locations;

use App\Models\Province;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('La extensión pdo_sqlite es requerida para pruebas con SQLite en memoria.');
        }

        parent::setUp();
    }

    public function test_countries_can_be_listed(): void
    {
        $this->seed(LocationSeeder::class);

        $response = $this->getJson('/api/countries');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Países encontrados.')
            ->assertJsonFragment([
                'code' => 'EC',
                'name' => 'Ecuador',
                'currency_code' => 'USD',
            ]);
    }

    public function test_country_can_be_retrieved_with_provinces(): void
    {
        $this->seed(LocationSeeder::class);

        $response = $this->getJson('/api/countries/EC');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'EC')
            ->assertJsonFragment([
                'code' => 'EC-P',
                'name' => 'Pichincha',
            ])
            ->assertJsonFragment([
                'code' => 'EC-G',
                'name' => 'Guayas',
            ]);
    }

    public function test_provinces_can_be_listed_by_country_code(): void
    {
        $this->seed(LocationSeeder::class);

        $response = $this->getJson('/api/countries/EC/provinces');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Provincias encontradas.')
            ->assertJsonPath('meta.country.code', 'EC')
            ->assertJsonFragment([
                'code' => 'EC-P',
                'name' => 'Pichincha',
            ]);
    }

    public function test_cities_can_be_listed_by_province(): void
    {
        $this->seed(LocationSeeder::class);

        $province = Province::where('code', 'EC-P')->firstOrFail();

        $response = $this->getJson("/api/provinces/{$province->id}/cities");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Ciudades encontradas.')
            ->assertJsonPath('meta.province.code', 'EC-P')
            ->assertJsonFragment([
                'code' => 'EC-P-QUITO',
                'name' => 'Quito',
            ])
            ->assertJsonFragment([
                'code' => 'EC-P-RUMINAHUI',
                'name' => 'Sangolquí',
            ]);
    }

    public function test_unknown_country_returns_standard_not_found_response(): void
    {
        $this->seed(LocationSeeder::class);

        $response = $this->getJson('/api/countries/ZZ');

        $response
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'not_found');
    }
}
