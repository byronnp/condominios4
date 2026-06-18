<?php

namespace Tests\Feature\Api\Catalogs;

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('La extensión pdo_sqlite es requerida para pruebas con SQLite en memoria.');
        }

        parent::setUp();
    }

    public function test_catalogs_can_be_listed(): void
    {
        $this->seed(CatalogSeeder::class);

        $response = $this->getJson('/api/catalogs');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Catálogos encontrados.')
            ->assertJsonFragment([
                'code' => 'document_types',
                'name' => 'Tipos de identificación',
            ]);
    }

    public function test_catalog_can_be_retrieved_with_active_items(): void
    {
        $this->seed(CatalogSeeder::class);

        $response = $this->getJson('/api/catalogs/document_types');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'document_types')
            ->assertJsonFragment([
                'code' => 'cedula',
                'name' => 'Cédula',
            ])
            ->assertJsonFragment([
                'code' => 'ruc',
                'name' => 'RUC',
            ])
            ->assertJsonFragment([
                'code' => 'pasaporte',
                'name' => 'Pasaporte',
            ]);
    }

    public function test_catalog_items_can_be_listed_by_catalog_code(): void
    {
        $this->seed(CatalogSeeder::class);

        $response = $this->getJson('/api/catalogs/payment_methods/items');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Items de catálogo encontrados.')
            ->assertJsonPath('meta.catalog.code', 'payment_methods')
            ->assertJsonFragment([
                'code' => 'transferencia',
                'name' => 'Transferencia bancaria',
            ]);
    }

    public function test_condominium_features_catalog_can_be_listed(): void
    {
        $this->seed(CatalogSeeder::class);

        $response = $this->getJson('/api/catalogs/condominium_features/items');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.catalog.code', 'condominium_features')
            ->assertJsonFragment([
                'code' => 'seguridad_24_7',
                'name' => 'Seguridad 24/7',
            ])
            ->assertJsonFragment([
                'code' => 'piscina',
                'name' => 'Piscina',
            ])
            ->assertJsonFragment([
                'code' => 'parqueadero_visitas',
                'name' => 'Parqueadero de visitas',
            ]);
    }

    public function test_condominium_types_catalog_can_be_listed(): void
    {
        $this->seed(CatalogSeeder::class);

        $response = $this->getJson('/api/catalogs/condominium_types/items');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.catalog.code', 'condominium_types')
            ->assertJsonFragment([
                'code' => 'residencial',
                'name' => 'Residencial',
            ])
            ->assertJsonFragment([
                'code' => 'mixto',
                'name' => 'Mixto',
            ])
            ->assertJsonFragment([
                'code' => 'conjunto_habitacional',
                'name' => 'Conjunto habitacional',
            ]);
    }

    public function test_unknown_catalog_returns_standard_not_found_response(): void
    {
        $this->seed(CatalogSeeder::class);

        $response = $this->getJson('/api/catalogs/no_existe');

        $response
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'not_found');
    }
}
