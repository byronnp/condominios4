<?php

namespace Tests\Feature\Api\Condominiums;

use App\Domain\Condominiums\Services\CondominiumCreationService;
use App\Models\CatalogItem;
use App\Models\Condominium;
use App\Models\Permission;
use App\Models\Province;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CondominiumPhaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('La extensión pdo_sqlite es requerida para pruebas con SQLite en memoria.');
        }

        parent::setUp();

        config([
            'jwt.secret' => 'testing-secret-with-at-least-32-chars',
            'jwt.access_ttl_minutes' => 60,
            'jwt.refresh_ttl_days' => 30,
        ]);

        $this->seed(DatabaseSeeder::class);
    }

    public function test_phase_four_seeders_create_condominium_security_menu_board_and_payment_methods(): void
    {
        $condominium = Condominium::with(['type', 'features', 'activeBillingSetting'])
            ->where('slug', 'condominio-los-cedros')
            ->firstOrFail();

        $this->assertSame(24, $condominium->total_units);
        $this->assertSame('residencial', $condominium->type?->code);
        $this->assertSame('A una cuadra del parque La Carolina.', $condominium->address_reference);
        $this->assertSame(2, $condominium->towers_count);
        $this->assertSame(24, $condominium->houses_count);
        $this->assertSame('USD', $condominium->activeBillingSetting?->currency);
        $this->assertContains('piscina', $condominium->features->pluck('code')->all());
        $this->assertDatabaseHas('roles', ['condominium_id' => $condominium->id, 'code' => 'administrador']);
        $this->assertDatabaseHas('permissions', ['code' => 'roles.manage']);
        $this->assertDatabaseHas('menus', ['code' => 'dashboard', 'category_code' => 'principal']);
        $this->assertDatabaseHas('menus', ['code' => 'reportes', 'category_code' => 'herramientas']);
        $this->assertDatabaseHas('condominium_boards', ['condominium_id' => $condominium->id, 'name' => 'Directiva 2026-2028']);
        $this->assertDatabaseHas('condominium_payment_methods', ['condominium_id' => $condominium->id, 'account_number' => '2200123456']);
    }

    public function test_authenticated_admin_can_get_menu_and_create_role(): void
    {
        $token = $this->loginToken();
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $permission = Permission::where('code', 'boards.view')->firstOrFail();

        $this->getJson('/api/auth/menu', [
            'Authorization' => "Bearer {$token}",
            'X-Condominium-Id' => $condominium->id,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['code' => 'principal'])
            ->assertJsonFragment(['code' => 'herramientas'])
            ->assertJsonFragment(['code' => 'pagos']);

        $roleResponse = $this->postJson("/api/condominiums/{$condominium->id}/roles", [
            'name' => 'Supervisor de mantenimiento',
            'description' => 'Puede revisar incidencias y mantenimientos.',
            'permission_ids' => [$permission->id],
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'supervisor_de_mantenimiento');

        $this->assertDatabaseHas('role_permission', [
            'role_id' => $roleResponse->json('data.id'),
            'permission_id' => $permission->id,
        ]);
    }

    public function test_permission_code_can_be_generated_from_module_and_action(): void
    {
        $token = $this->loginToken();

        $this->postJson('/api/permissions', [
            'module' => 'incidents',
            'action' => 'approve',
            'name' => 'Aprobar incidencias',
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'incidents.approve');
    }

    public function test_condominium_can_be_created_with_normalized_location(): void
    {
        $token = $this->loginToken();
        $province = Province::where('code', 'EC-P')->firstOrFail();
        $city = $province->cities()->where('code', 'EC-P-QUITO')->firstOrFail();

        $response = $this->postJson('/api/condominiums', [
            'name' => 'Condominio Location Test',
            'ruc' => '1791234567001',
            'email' => 'location.test@example.com',
            'phone' => '0991234567',
            'address' => 'Av. Test N1-23',
            'country_code' => 'EC',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'total_units' => 12,
        ], [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.country_code', 'EC')
            ->assertJsonPath('data.province_id', $province->id)
            ->assertJsonPath('data.city_id', $city->id);

        $this->assertDatabaseHas('condominiums', [
            'slug' => 'condominio-location-test',
            'country_code' => 'EC',
            'province_id' => $province->id,
            'city_id' => $city->id,
        ]);
    }

    public function test_condominium_form_payload_is_normalized_before_creation(): void
    {
        $logoDisk = config('filesystems.logo_disk', 'public');

        Storage::fake($logoDisk);

        $token = $this->loginToken();
        $province = Province::where('code', 'EC-G')->firstOrFail();
        $city = $province->cities()->where('code', 'EC-G-GUAYAQUIL')->firstOrFail();
        $type = CatalogItem::whereHas('catalog', fn ($query) => $query->where('code', 'condominium_types'))
            ->where('code', 'residencial')
            ->firstOrFail();
        $featureIds = CatalogItem::whereHas('catalog', fn ($query) => $query->where('code', 'condominium_features'))
            ->whereIn('code', ['piscina', 'gimnasio', 'seguridad_24_7', 'parqueadero_visitas'])
            ->pluck('id')
            ->all();
        $documentType = CatalogItem::whereHas('catalog', fn ($query) => $query->where('code', 'document_types'))
            ->where('code', 'cedula')
            ->firstOrFail();

        $response = $this->post('/api/condominiums', [
            'name' => 'Condominio Vista Verde',
            'ruc' => '0999999999001',
            'type' => 'Residencial',
            'description' => 'Condominio residencial con áreas comunes y seguridad privada.',
            'status' => 'Activo',
            'country_code' => 'EC',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'direction' => 'Av. Principal 123 y Calle Secundaria',
            'reference' => 'Frente al parque central',
            'latitude' => -2.170998,
            'longitude' => -79.922359,
            'currency' => 'USD',
            'towers' => 4,
            'houses' => 120,
            'characteristics' => $featureIds,
            'admin_name' => 'Carlos',
            'admin_last_name' => 'Ramírez',
            'admin_document_type' => 'Cédula',
            'admin_id_number' => '0912345678',
            'admin_email' => 'carlos.ramirez@example.com',
            'admin_phone' => '+593 99 123 4567',
            'admin_status' => 'Activo',
            'logo' => UploadedFile::fake()->create('logo.png', 12, 'image/png'),
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Condominio Vista Verde')
            ->assertJsonPath('data.address', 'Av. Principal 123 y Calle Secundaria')
            ->assertJsonPath('data.type.code', 'residencial')
            ->assertJsonPath('data.currency', 'USD')
            ->assertJsonPath('data.towers_count', 4)
            ->assertJsonPath('data.houses_count', 120)
            ->assertJsonPath('data.administrator.email', 'carlos.ramirez@example.com')
            ->assertJsonCount(4, 'data.features')
            ->assertJsonStructure([
                'data' => [
                    'logo_path',
                    'logo_url',
                ],
            ]);

        $this->assertDatabaseHas('condominiums', [
            'slug' => 'condominio-vista-verde',
            'condominium_type_id' => $type->id,
            'description' => 'Condominio residencial con áreas comunes y seguridad privada.',
            'is_active' => true,
            'address' => 'Av. Principal 123 y Calle Secundaria',
            'address_reference' => 'Frente al parque central',
            'towers_count' => 4,
            'houses_count' => 120,
        ]);

        $condominium = Condominium::where('slug', 'condominio-vista-verde')->firstOrFail();

        $this->assertNotNull($condominium->logo_path);
        Storage::disk($logoDisk)->assertExists($condominium->logo_path);

        $this->assertDatabaseHas('condominium_billing_settings', [
            'condominium_id' => $condominium->id,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $this->assertCount(4, $condominium->features()->get());
        foreach ($featureIds as $featureId) {
            $this->assertDatabaseHas('condominium_features', [
                'condominium_id' => $condominium->id,
                'catalog_item_id' => $featureId,
            ]);
        }

        $this->assertDatabaseHas('users', [
            'name' => 'Carlos Ramírez',
            'email' => 'carlos.ramirez@example.com',
            'document_type_id' => $documentType->id,
            'document_number' => '0912345678',
            'phone' => '+593 99 123 4567',
            'is_access_enabled' => true,
        ]);

        $administrator = $condominium->users()->where('email', 'carlos.ramirez@example.com')->firstOrFail();
        $role = $condominium->roles()->where('code', 'administrador')->firstOrFail();

        $this->assertDatabaseHas('condominium_user_role', [
            'condominium_user_id' => $administrator->pivot->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_condominium_location_must_keep_province_and_city_consistent(): void
    {
        $token = $this->loginToken();
        $pichincha = Province::where('code', 'EC-P')->firstOrFail();
        $guayasCity = Province::where('code', 'EC-G')->firstOrFail()
            ->cities()
            ->where('code', 'EC-G-GUAYAQUIL')
            ->firstOrFail();

        $this->postJson('/api/condominiums', [
            'name' => 'Condominio Location Invalid',
            'address' => 'Av. Test N1-23',
            'country_code' => 'EC',
            'province_id' => $pichincha->id,
            'city_id' => $guayasCity->id,
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.city_id.0', 'La ciudad no pertenece a la provincia seleccionada.');
    }

    public function test_condominium_creation_rejects_invalid_catalog_labels(): void
    {
        $token = $this->loginToken();

        $cases = [
            'type' => [
                'type' => 'Tipo inexistente',
            ],
            'characteristics.0' => [
                'characteristics' => [999999],
            ],
            'admin_document_type' => [
                'admin_name' => 'Carlos',
                'admin_last_name' => 'Ramírez',
                'admin_document_type' => 'Documento inexistente',
                'admin_id_number' => '0912345678',
                'admin_email' => 'admin.invalid@example.com',
                'admin_status' => 'Activo',
            ],
        ];

        foreach ($cases as $field => $payload) {
            $this->postJson('/api/condominiums', array_merge([
                'name' => 'Condominio Validación '.$field,
                'address' => 'Av. Validación N1-23',
            ], $payload), [
                'Authorization' => "Bearer {$token}",
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors($field);
        }
    }

    public function test_condominium_creation_rejects_non_image_logo(): void
    {
        $token = $this->loginToken();

        $this->post('/api/condominiums', [
            'name' => 'Condominio Logo Inválido',
            'address' => 'Av. Validación N1-23',
            'logo' => UploadedFile::fake()->create('documento.txt', 4, 'text/plain'),
        ], [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('logo');
    }

    public function test_condominium_creation_rejects_duplicate_ruc(): void
    {
        $token = $this->loginToken();

        $this->postJson('/api/condominiums', [
            'name' => 'Condominio RUC Duplicado',
            'ruc' => '1799999999001',
            'address' => 'Av. Validación N1-23',
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ruc');
    }

    public function test_condominium_creation_removes_logo_when_database_transaction_fails(): void
    {
        $logoDisk = config('filesystems.logo_disk', 'public');

        Storage::fake($logoDisk);

        try {
            app(CondominiumCreationService::class)->create([
                'name' => 'Condominio Duplicado',
                'slug' => 'condominio-los-cedros',
                'address' => 'Av. Duplicada N1-23',
                'country_code' => 'EC',
                'total_units' => 0,
                'is_active' => true,
            ], logo: UploadedFile::fake()->create('logo.png', 12, 'image/png'));

            $this->fail('Se esperaba una excepción por slug duplicado.');
        } catch (QueryException) {
            $this->assertSame([], Storage::disk($logoDisk)->allFiles('condominiums/logos'));
        }
    }

    private function loginToken(): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'byron_np@hotmail.com',
            'password' => 'admin123',
        ])->assertOk();

        return $response->json('data.access_token');
    }
}
