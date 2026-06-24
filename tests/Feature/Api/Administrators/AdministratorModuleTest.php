<?php

namespace Tests\Feature\Api\Administrators;

use App\Models\CatalogItem;
use App\Models\Condominium;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdministratorModuleTest extends TestCase
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

    public function test_platform_admin_can_list_and_filter_administrators(): void
    {
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();

        $this->getJson("/api/administrators?condominium_id={$condominium->id}&status=active&per_page=2", $this->headers())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'first_name',
                        'last_name',
                        'email',
                        'document_type',
                        'is_access_enabled',
                        'condominiums',
                    ],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    public function test_platform_admin_can_create_update_and_change_administrator_status(): void
    {
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $documentType = $this->documentType();

        $response = $this->postJson('/api/administrators', [
            'first_name' => 'Carlos',
            'last_name' => 'Ramírez',
            'email' => 'carlos.ramirez@example.com',
            'country' => 'EC',
            'document_type_id' => $documentType->id,
            'document_number' => '0912345678',
            'phone' => '0991234567',
            'is_access_enabled' => false,
            'condominium_ids' => [$condominium->id],
        ], $this->headers())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Carlos Ramírez')
            ->assertJsonPath('data.first_name', 'Carlos')
            ->assertJsonPath('data.last_name', 'Ramírez')
            ->assertJsonPath('data.email', 'carlos.ramirez@example.com')
            ->assertJsonPath('data.is_access_enabled', false)
            ->assertJsonPath('data.condominiums.0.id', $condominium->id);

        $administratorId = $response->json('data.id');

        $this->assertDatabaseHas('condominium_user', [
            'condominium_id' => $condominium->id,
            'user_id' => $administratorId,
            'is_active' => true,
            'deleted_at' => null,
        ]);

        $this->assertAdministratorRoleAssignment($administratorId, $condominium->id);

        $this->putJson("/api/administrators/{$administratorId}", [
            'first_name' => 'Carlos Andrés',
            'phone' => '0987654321',
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('data.name', 'Carlos Andrés Ramírez')
            ->assertJsonPath('data.first_name', 'Carlos Andrés')
            ->assertJsonPath('data.phone', '0987654321');

        $this->patchJson("/api/administrators/{$administratorId}/status", [
            'is_access_enabled' => true,
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('data.is_access_enabled', true);
    }

    public function test_platform_admin_can_assign_and_remove_a_condominium(): void
    {
        $administrator = User::where('email', 'byronnp@gmail.com')->firstOrFail();
        $condominium = Condominium::create([
            'name' => 'Condominio Nuevo',
            'slug' => 'condominio-nuevo',
            'address' => 'Av. Nueva 123',
            'country_code' => 'EC',
            'total_units' => 10,
            'is_active' => true,
        ]);

        $this->postJson("/api/administrators/{$administrator->id}/condominiums", [
            'condominium_id' => $condominium->id,
        ], $this->headers())
            ->assertOk()
            ->assertJsonFragment(['slug' => 'condominio-nuevo']);

        $this->assertAdministratorRoleAssignment($administrator->id, $condominium->id);

        $this->deleteJson(
            "/api/administrators/{$administrator->id}/condominiums/{$condominium->id}",
            [],
            $this->headers(),
        )
            ->assertOk();

        $this->assertDatabaseMissing('condominium_user', [
            'condominium_id' => $condominium->id,
            'user_id' => $administrator->id,
            'deleted_at' => null,
        ]);
    }

    public function test_destroy_disables_access_and_removes_administrator_assignments(): void
    {
        $administrator = User::where('email', 'byronnp@gmail.com')->firstOrFail();

        $this->deleteJson("/api/administrators/{$administrator->id}", [], $this->headers())
            ->assertOk()
            ->assertJsonPath('message', 'Administrador eliminado correctamente.');

        $this->assertDatabaseHas('users', [
            'id' => $administrator->id,
            'is_access_enabled' => false,
        ]);

        $this->getJson("/api/administrators/{$administrator->id}", $this->headers())
            ->assertNotFound();
    }

    public function test_administrator_creation_validates_required_fields_and_authentication(): void
    {
        $this->postJson('/api/administrators', [])
            ->assertUnauthorized();

        $this->postJson('/api/administrators', [], $this->headers())
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'country',
                'document_type_id',
                'document_number',
                'condominium_ids',
            ]);
    }

    private function documentType(): CatalogItem
    {
        return CatalogItem::whereHas('catalog', fn ($query) => $query->where('code', 'document_types'))
            ->where('code', 'cedula')
            ->firstOrFail();
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'byron_np@hotmail.com',
            'password' => 'admin123',
        ])->assertOk();

        return ['Authorization' => 'Bearer '.$response->json('data.access_token')];
    }

    private function assertAdministratorRoleAssignment(int $administratorId, int $condominiumId): void
    {
        $exists = DB::table('condominium_user')
            ->join('condominium_user_role', 'condominium_user_role.condominium_user_id', '=', 'condominium_user.id')
            ->join('roles', 'roles.id', '=', 'condominium_user_role.role_id')
            ->where('condominium_user.condominium_id', $condominiumId)
            ->where('condominium_user.user_id', $administratorId)
            ->where('roles.code', 'administrador')
            ->whereNull('condominium_user.deleted_at')
            ->whereNull('condominium_user_role.deleted_at')
            ->exists();

        $this->assertTrue($exists);
    }
}
