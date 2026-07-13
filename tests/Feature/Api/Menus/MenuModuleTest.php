<?php

namespace Tests\Feature\Api\Menus;

use App\Models\Condominium;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MenuModuleTest extends TestCase
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

    public function test_normal_user_cannot_list_or_create_menus(): void
    {
        $headers = $this->headers('swagger.admin@example.com');

        $this->getJson('/api/menus', $headers)->assertForbidden();

        $this->postJson('/api/menus', [
            'name' => 'Nuevo menú',
            'code' => 'nuevo_menu',
        ], $headers)->assertForbidden();
    }

    public function test_senior_administrator_can_list_and_create_menus(): void
    {
        $headers = $this->headers('byron_np@hotmail.com', 'admin123');

        $this->getJson('/api/menus', $headers)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['code' => 'dashboard']);

        $this->postJson('/api/menus', [
            'name' => 'Supervisión',
            'code' => 'supervision',
            'path' => '/supervision',
            'icon' => 'eye',
            'sort_order' => 99,
            'is_active' => true,
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.code', 'supervision');
    }

    public function test_auth_menu_requires_condominium_context_when_user_has_multiple_active_condominiums(): void
    {
        $user = User::where('email', 'swagger.admin@example.com')->firstOrFail();
        $secondCondominium = Condominium::create([
            'name' => 'Condominio Dos',
            'slug' => 'condominio-dos-menu-test',
            'address' => 'Av. Dos 123',
            'country_code' => 'EC',
            'total_units' => 2,
            'is_active' => true,
        ]);
        $secondRole = Role::create([
            'condominium_id' => $secondCondominium->id,
            'name' => 'Administrador',
            'code' => 'administrador_test',
            'is_system' => false,
            'is_active' => true,
        ]);
        $this->attachUserToCondominium($user, $secondCondominium, $secondRole);

        $this->getJson('/api/auth/menu', $this->headers('swagger.admin@example.com'))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'condominium_context_required');
    }

    public function test_auth_menu_accepts_valid_condominium_context_for_multi_condominium_user(): void
    {
        $user = User::where('email', 'swagger.admin@example.com')->firstOrFail();
        $firstCondominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $secondCondominium = Condominium::create([
            'name' => 'Condominio Dos',
            'slug' => 'condominio-dos-menu-ok',
            'address' => 'Av. Dos 123',
            'country_code' => 'EC',
            'total_units' => 2,
            'is_active' => true,
        ]);
        $secondRole = Role::create([
            'condominium_id' => $secondCondominium->id,
            'name' => 'Administrador',
            'code' => 'administrador_test_2',
            'is_system' => false,
            'is_active' => true,
        ]);
        $administratorRole = Role::where('condominium_id', $firstCondominium->id)->where('code', 'administrador')->firstOrFail();
        $secondRole->permissions()->sync($administratorRole->permissions()->pluck('permissions.id')->all());
        $this->attachUserToCondominium($user, $secondCondominium, $secondRole);

        $this->getJson('/api/auth/menu', [
            ...$this->headers('swagger.admin@example.com'),
            'X-Condominium-Id' => (string) $secondCondominium->id,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['code' => 'usuarios'])
            ->assertJsonMissing(['code' => 'condominios']);
    }

    public function test_auth_menu_rejects_condominium_ajeno_and_inactive(): void
    {
        $user = User::where('email', 'swagger.admin@example.com')->firstOrFail();
        $firstCondominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $otherCondominium = Condominium::create([
            'name' => 'Condominio Extra',
            'slug' => 'condominio-extra-menu-ajeno',
            'address' => 'Av. Extra 123',
            'country_code' => 'EC',
            'total_units' => 2,
            'is_active' => true,
        ]);

        $this->getJson('/api/auth/menu', [
            ...$this->headers('swagger.admin@example.com'),
            'X-Condominium-Id' => (string) $otherCondominium->id,
        ])
            ->assertForbidden()
            ->assertJsonPath('code', 'condominium_forbidden');

        $firstCondominium->update(['is_active' => false]);

        $this->getJson('/api/auth/menu', [
            ...$this->headers('swagger.admin@example.com'),
            'X-Condominium-Id' => (string) $firstCondominium->id,
        ])
            ->assertForbidden()
            ->assertJsonPath('code', 'condominium_inactive');
    }

    public function test_all_permissions_attached_to_menus_exist_in_permissions_catalog(): void
    {
        $permissionCodes = Permission::query()->pluck('code')->all();

        $missing = Menu::query()
            ->with('permissions')
            ->get()
            ->flatMap(fn (Menu $menu) => $menu->permissions->pluck('code'))
            ->unique()
            ->reject(fn (string $code) => in_array($code, $permissionCodes, true))
            ->values()
            ->all();

        $this->assertSame([], $missing);
    }

    private function headers(string $email, string $password = 'Swagger123!'): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ])->assertOk();

        return ['Authorization' => 'Bearer '.$response->json('data.access_token')];
    }

    private function attachUserToCondominium(User $user, Condominium $condominium, Role $role): void
    {
        $condominium->users()->syncWithoutDetaching([
            $user->id => [
                'is_active' => true,
                'joined_at' => now(),
            ],
        ]);

        $condominiumUserId = DB::table('condominium_user')
            ->where('user_id', $user->id)
            ->where('condominium_id', $condominium->id)
            ->value('id');

        DB::table('condominium_user_role')->updateOrInsert([
            'condominium_user_id' => $condominiumUserId,
            'role_id' => $role->id,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }
}
