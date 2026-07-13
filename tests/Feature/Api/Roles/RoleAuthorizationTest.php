<?php

namespace Tests\Feature\Api\Roles;

use App\Models\Condominium;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
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

    public function test_user_from_condominium_a_cannot_access_roles_of_condominium_b(): void
    {
        $condominiumA = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $condominiumB = Condominium::where('slug', 'condominio-jardines-del-valle')->firstOrFail();
        $roleA = Role::where('condominium_id', $condominiumA->id)->where('code', 'administrador')->firstOrFail();

        $this->getJson("/api/condominiums/{$condominiumB->id}/roles/{$roleA->id}", $this->headers('swagger.admin@example.com'))
            ->assertNotFound();
    }

    public function test_user_without_roles_view_receives_forbidden(): void
    {
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $role = Role::where('condominium_id', $condominium->id)->where('code', 'administrador')->firstOrFail();
        $permission = Permission::where('code', 'roles.view')->firstOrFail();

        DB::table('role_permission')
            ->where('role_id', $role->id)
            ->where('permission_id', $permission->id)
            ->delete();

        $this->getJson("/api/condominiums/{$condominium->id}/roles", $this->headers('swagger.admin@example.com'))
            ->assertForbidden();
    }

    public function test_user_with_roles_view_can_consult_roles(): void
    {
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();

        $this->getJson("/api/condominiums/{$condominium->id}/roles", $this->headers('byron_np@hotmail.com', 'admin123'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['code' => 'administrador']);
    }

    public function test_user_without_roles_manage_receives_forbidden_when_creating_roles(): void
    {
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $role = Role::where('condominium_id', $condominium->id)->where('code', 'administrador')->firstOrFail();
        $permission = Permission::where('code', 'roles.manage')->firstOrFail();

        DB::table('role_permission')
            ->where('role_id', $role->id)
            ->where('permission_id', $permission->id)
            ->delete();

        $this->postJson("/api/condominiums/{$condominium->id}/roles", [
            'name' => 'Supervisor temporal',
            'description' => 'Rol de prueba',
        ], $this->headers('swagger.admin@example.com'))
            ->assertForbidden();
    }

    public function test_user_with_roles_manage_can_create_roles(): void
    {
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();

        $this->postJson("/api/condominiums/{$condominium->id}/roles", [
            'name' => 'Supervisor temporal',
            'description' => 'Rol de prueba',
        ], $this->headers('byron_np@hotmail.com', 'admin123'))
            ->assertCreated()
            ->assertJsonPath('data.code', 'supervisor_temporal');
    }

    public function test_user_cannot_modify_system_roles(): void
    {
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $role = Role::where('condominium_id', $condominium->id)->where('code', 'administrador')->firstOrFail();

        $this->putJson("/api/condominiums/{$condominium->id}/roles/{$role->id}", [
            'name' => 'Administrador actualizado',
            'code' => 'administrador_actualizado',
            'description' => 'Intento de modificación',
            'is_active' => true,
        ], $this->headers('swagger.admin@example.com'))
            ->assertForbidden();
    }

    private function headers(string $email, string $password = 'Swagger123!'): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ])->assertOk();

        return ['Authorization' => 'Bearer '.$response->json('data.access_token')];
    }
}
