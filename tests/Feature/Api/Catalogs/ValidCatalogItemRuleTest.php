<?php

namespace Tests\Feature\Api\Catalogs;

use App\Models\Catalog;
use App\Rules\ValidCatalogItem;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidCatalogItemRuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('La extensión pdo_sqlite es requerida para pruebas con SQLite en memoria.');
        }

        parent::setUp();
    }

    public function test_it_accepts_active_items_from_expected_catalog(): void
    {
        $this->seed(CatalogSeeder::class);

        $item = Catalog::where('code', 'document_types')->firstOrFail()
            ->items()
            ->where('code', 'cedula')
            ->firstOrFail();

        $validator = Validator::make([
            'document_type_id' => $item->id,
        ], [
            'document_type_id' => [new ValidCatalogItem('document_types')],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_it_rejects_items_from_another_catalog(): void
    {
        $this->seed(CatalogSeeder::class);

        $item = Catalog::where('code', 'payment_methods')->firstOrFail()
            ->items()
            ->where('code', 'efectivo')
            ->firstOrFail();

        $validator = Validator::make([
            'document_type_id' => $item->id,
        ], [
            'document_type_id' => [new ValidCatalogItem('document_types')],
        ]);

        $this->assertFalse($validator->passes());
    }
}
