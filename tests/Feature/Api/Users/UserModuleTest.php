<?php

namespace Tests\Feature\Api\Users;

use App\Models\CatalogItem;
use App\Models\Condominium;
use App\Models\Role;
use App\Models\User;
use App\Policies\UserPolicy;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('La extensión pdo_sqlite es requerida para pruebas con SQLite en memoria.');
        }

        parent::setUp();
        config(['jwt.secret' => 'testing-secret-with-at-least-32-chars', 'jwt.access_ttl_minutes' => 60, 'jwt.refresh_ttl_days' => 30]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_senior_administrator_can_create_and_list_any_user(): void
    {
        $condominium = Condominium::firstOrFail();
        $role = Role::where('condominium_id', $condominium->id)->where('code', 'contabilidad')->firstOrFail();

        $response = $this->postJson("/api/condominiums/{$condominium->id}/users", [
            'first_name' => 'Ana', 'last_name' => 'Pérez', 'email' => 'ana@example.com',
            'country' => 'EC', 'document_type_id' => $this->passportId(), 'document_number' => 'PA123456',
            'role_id' => $role->id,
        ], $this->headers('byron_np@hotmail.com'))->assertCreated()
            ->assertJsonPath('data.assignments.0.role_code', 'contabilidad');

        $this->getJson("/api/condominiums/{$condominium->id}/users?search=ana@example.com", $this->headers('byron_np@hotmail.com'))
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $response->json('data.id'));
    }

    public function test_condominium_administrator_is_scoped_and_backend_forces_its_condominium(): void
    {
        $condominium = Condominium::firstOrFail();
        $role = Role::where('condominium_id', $condominium->id)->where('code', 'residente')->firstOrFail();

        $response = $this->postJson("/api/condominiums/{$condominium->id}/users", [
            'first_name' => 'Luis', 'last_name' => 'Torres', 'email' => 'luis@example.com',
            'country' => 'EC', 'document_type_id' => $this->passportId(), 'document_number' => 'PB123456',
            'role_id' => $role->id,
        ], $this->headers('byronnp@gmail.com'))->assertCreated();

        $this->assertDatabaseHas('condominium_user', ['user_id' => $response->json('data.id'), 'condominium_id' => $condominium->id, 'deleted_at' => null]);
        $this->getJson("/api/condominiums/{$condominium->id}/users", $this->headers('byronnp@gmail.com'))->assertOk()
            ->assertJsonMissing(['email' => 'byron_np@hotmail.com']);
    }

    public function test_nested_condominium_user_routes_use_route_context(): void
    {
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $role = Role::where('condominium_id', $condominium->id)->where('code', 'residente')->firstOrFail();

        $response = $this->postJson("/api/condominiums/{$condominium->id}/users", [
            'first_name' => 'Ruta',
            'last_name' => 'Contexto',
            'email' => 'ruta.contexto@example.com',
            'country' => 'EC',
            'document_type_id' => $this->passportId(),
            'document_number' => 'PX123456',
            'role_id' => $role->id,
        ], $this->headers('byronnp@gmail.com'))
            ->assertCreated()
            ->assertJsonPath('data.email', 'ruta.contexto@example.com')
            ->assertJsonPath('data.assignments.0.condominium_id', $condominium->id);

        $userId = $response->json('data.id');

        $this->assertDatabaseHas('condominium_user', [
            'user_id' => $userId,
            'condominium_id' => $condominium->id,
            'deleted_at' => null,
        ]);

        $this->getJson("/api/condominiums/{$condominium->id}/users?search=ruta.contexto@example.com", $this->headers('byronnp@gmail.com'))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $userId);
    }

    public function test_nested_user_route_rejects_forbidden_condominium_context_with_business_code(): void
    {
        $other = $this->createSecondCondominium();

        $this->getJson("/api/condominiums/{$other->id}/users", $this->headers('byronnp@gmail.com'))
            ->assertForbidden()
            ->assertJsonPath('code', 'condominium_forbidden');
    }

    public function test_condominium_administrator_cannot_assign_senior_role(): void
    {
        $seniorRole = Role::whereNull('condominium_id')->where('code', 'administrador_senior')->firstOrFail();
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();

        $this->postJson("/api/condominiums/{$condominium->id}/users", [
            'first_name' => 'Root', 'email' => 'root2@example.com', 'country' => 'EC',
            'document_type_id' => $this->passportId(), 'document_number' => 'PC123456',
            'role_id' => $seniorRole->id,
        ], $this->headers('byronnp@gmail.com'))->assertUnprocessable()
            ->assertJsonValidationErrors('assignments.0.role_id');
    }

    public function test_status_change_does_not_soft_delete_user(): void
    {
        $target = User::where('email', 'byronnp@gmail.com')->firstOrFail();
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();

        $this->patchJson("/api/condominiums/{$condominium->id}/users/{$target->id}/status", ['is_access_enabled' => false], $this->headers('byron_np@hotmail.com'))
            ->assertOk()->assertJsonPath('data.assignments.0.is_active', 0);
        $this->assertDatabaseHas('condominium_user', ['user_id' => $target->id, 'condominium_id' => $condominium->id, 'is_active' => false, 'deleted_at' => null]);
    }

    public function test_condominium_administrator_cannot_view_user_from_another_condominium(): void
    {
        $other = $this->createSecondCondominium();
        $residentRole = Role::where('condominium_id', $other->id)->where('code', 'residente')->firstOrFail();
        $user = User::create([
            'first_name' => 'Usuario', 'last_name' => 'Externo', 'email' => 'externo@example.com',
            'country' => 'EC', 'document_type_id' => $this->passportId(), 'document_number' => 'PD123456',
            'password' => null, 'is_access_enabled' => true,
        ]);
        $this->assign($user, $other, $residentRole);

        $this->getJson("/api/condominiums/{$other->id}/users/{$user->id}", $this->headers('byronnp@gmail.com'))->assertForbidden();
        $this->putJson("/api/condominiums/{$other->id}/users/{$user->id}", ['first_name' => 'Intruso'], $this->headers('byronnp@gmail.com'))->assertForbidden();
    }

    public function test_shared_user_response_and_local_changes_are_limited_to_managed_condominium(): void
    {
        $first = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $second = $this->createSecondCondominium();
        $firstResident = Role::where('condominium_id', $first->id)->where('code', 'residente')->firstOrFail();
        $firstAccounting = Role::where('condominium_id', $first->id)->where('code', 'contabilidad')->firstOrFail();
        $secondResident = Role::where('condominium_id', $second->id)->where('code', 'residente')->firstOrFail();

        $user = User::create([
            'first_name' => 'Usuario', 'last_name' => 'Compartido', 'email' => 'compartido@example.com',
            'country' => 'EC', 'document_type_id' => $this->passportId(), 'document_number' => 'PE123456',
            'password' => null, 'is_access_enabled' => true,
        ]);
        $this->assign($user, $first, $firstResident);
        $this->assign($user, $second, $secondResident);

        $actor = User::where('email', 'byronnp@gmail.com')->firstOrFail();
        $this->assertContains($first->id, $actor->manageableCondominiumIds('users.view'));
        $this->assertContains($first->id, $user->condominiums()->wherePivot('is_active', true)->wherePivotNull('deleted_at')->pluck('condominiums.id')->all());
        $this->assertFalse($user->isPlatformAdmin());
        $this->assertTrue(collect($user->condominiums()->wherePivot('is_active', true)->wherePivotNull('deleted_at')->pluck('condominiums.id')->map(fn ($id): int => (int) $id)->all())->intersect($actor->manageableCondominiumIds('users.view'))->isNotEmpty());
        $this->assertTrue(app(UserPolicy::class)->view($actor, $user));

        $headers = $this->headers('byronnp@gmail.com');
        $this->getJson("/api/condominiums/{$first->id}/users/{$user->id}", $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data.assignments')
            ->assertJsonPath('data.assignments.0.condominium_id', $first->id);

        $this->putJson("/api/condominiums/{$first->id}/users/{$user->id}", [
            'role_id' => $firstAccounting->id,
        ], $headers)->assertOk()->assertJsonCount(1, 'data.assignments');

        $this->assertDatabaseHas('condominium_user_role', [
            'condominium_user_id' => DB::table('condominium_user')->where('user_id', $user->id)->where('condominium_id', $second->id)->value('id'),
            'role_id' => $secondResident->id,
            'deleted_at' => null,
        ]);

        $this->patchJson("/api/condominiums/{$first->id}/users/{$user->id}/status", ['is_access_enabled' => false], $headers)
            ->assertOk()->assertJsonPath('data.assignments.0.is_active', 0);

        $this->assertDatabaseHas('condominium_user', ['user_id' => $user->id, 'condominium_id' => $first->id, 'is_active' => false]);
        $this->assertDatabaseHas('condominium_user', ['user_id' => $user->id, 'condominium_id' => $second->id, 'is_active' => true]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_access_enabled' => true]);
    }

    private function passportId(): int
    {
        return CatalogItem::whereHas('catalog', fn ($query) => $query->where('code', 'document_types'))->where('code', 'pasaporte')->value('id');
    }

    private function headers(string $email): array
    {
        $response = $this->postJson('/api/auth/login', ['email' => $email, 'password' => 'admin123'])->assertOk();

        return ['Authorization' => 'Bearer '.$response->json('data.access_token')];
    }

    private function createSecondCondominium(): Condominium
    {
        $condominium = Condominium::create([
            'name' => 'Condominio Dos', 'slug' => 'condominio-dos', 'address' => 'Av. Dos 123',
            'country_code' => 'EC', 'total_units' => 2, 'is_active' => true,
        ]);
        $this->seed(RolePermissionSeeder::class);

        return $condominium;
    }

    private function assign(User $user, Condominium $condominium, Role $role): void
    {
        $condominium->users()->attach($user->id, ['is_active' => true, 'joined_at' => now()]);
        DB::table('condominium_user_role')->insert([
            'condominium_user_id' => DB::table('condominium_user')->where('user_id', $user->id)->where('condominium_id', $condominium->id)->value('id'),
            'role_id' => $role->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
