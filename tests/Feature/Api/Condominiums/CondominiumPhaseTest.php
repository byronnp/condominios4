<?php

namespace Tests\Feature\Api\Condominiums;

use App\Models\Condominium;
use App\Models\Permission;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();

        $this->assertSame(24, $condominium->total_units);
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

    private function loginToken(): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'byron_np@hotmail.com',
            'password' => 'admin123',
        ])->assertOk();

        return $response->json('data.access_token');
    }
}
