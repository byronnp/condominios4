<?php

namespace Tests\Feature\Api\Administrators;

use App\Mail\UserAccessInvitationMail;
use App\Models\CatalogItem;
use App\Models\Condominium;
use App\Models\User;
use App\Models\UserAccessInvitation;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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

    public function test_senior_admin_can_assign_new_user_as_condominium_administrator(): void
    {
        Mail::fake();

        $condominium = $this->condominium();

        $response = $this->postJson("/api/condominiums/{$condominium->id}/administrators", [
            'first_name' => 'Carlos',
            'last_name' => 'Ramírez',
            'email' => 'carlos.ramirez@example.com',
            'country' => 'EC',
            'document_type_id' => $this->documentType()->id,
            'document_number' => '0912345678',
            'phone' => '0991234567',
        ], $this->headers('byron_np@hotmail.com'))
            ->assertCreated()
            ->assertJsonPath('data.email', 'carlos.ramirez@example.com')
            ->assertJsonPath('data.administrator_type', 'condominium')
            ->assertJsonPath('data.condominiums.0.id', $condominium->id)
            ->assertJsonPath('data.access_status', 'pending_activation');

        $administratorId = $response->json('data.id');

        $this->assertAdministratorRoleAssignment($administratorId, $condominium->id);

        $invitation = UserAccessInvitation::query()
            ->where('user_id', $administratorId)
            ->where('condominium_id', $condominium->id)
            ->firstOrFail();

        Mail::assertQueued(UserAccessInvitationMail::class, fn (UserAccessInvitationMail $mail): bool => $mail->invitation->is($invitation));
    }

    public function test_assigning_active_existing_user_does_not_send_invitation_or_duplicate_membership(): void
    {
        Mail::fake();

        $condominium = $this->condominium();
        $existing = User::create([
            'first_name' => 'Usuario',
            'last_name' => 'Activo',
            'email' => 'activo.admin@example.com',
            'country' => 'EC',
            'document_type_id' => $this->documentType()->id,
            'document_number' => '0912345679',
            'password' => 'Secret123!',
            'is_access_enabled' => true,
        ]);

        $this->postJson("/api/condominiums/{$condominium->id}/administrators", [
            'first_name' => 'Usuario',
            'last_name' => 'Activo',
            'email' => 'activo.admin@example.com',
            'country' => 'EC',
            'document_type_id' => $this->documentType()->id,
            'document_number' => '0912345679',
        ], $this->headers('byron_np@hotmail.com'))
            ->assertCreated()
            ->assertJsonPath('data.id', $existing->id)
            ->assertJsonPath('data.is_access_enabled', true)
            ->assertJsonPath('data.invitation', null);

        $this->assertSame(1, DB::table('condominium_user')
            ->where('user_id', $existing->id)
            ->where('condominium_id', $condominium->id)
            ->whereNull('deleted_at')
            ->count());
        $this->assertAdministratorRoleAssignment($existing->id, $condominium->id);
        Mail::assertNothingQueued();
    }

    public function test_condominium_admin_lists_only_administrators_in_route_condominium(): void
    {
        $condominium = $this->condominium();
        $administrator = User::where('email', 'byronnp@gmail.com')->firstOrFail();

        $this->getJson("/api/condominiums/{$condominium->id}/administrators?per_page=100", $this->headers('byronnp@gmail.com'))
            ->assertOk()
            ->assertJsonFragment(['id' => $administrator->id])
            ->assertJsonMissing(['email' => 'byron_np@hotmail.com']);
    }

    public function test_condominium_admin_receives_business_code_outside_scope(): void
    {
        $other = Condominium::create([
            'name' => 'Condominio Fuera de Alcance',
            'slug' => 'condominio-fuera-alcance',
            'address' => 'Av. Otra 123',
            'country_code' => 'EC',
            'total_units' => 10,
            'is_active' => true,
        ]);

        $this->getJson("/api/condominiums/{$other->id}/administrators", $this->headers('byronnp@gmail.com'))
            ->assertForbidden()
            ->assertJsonPath('code', 'condominium_forbidden');
    }

    public function test_senior_admin_can_update_status_and_remove_administrator_from_condominium(): void
    {
        $condominium = $this->condominium();
        $administrator = User::where('email', 'byronnp@gmail.com')->firstOrFail();

        $this->putJson("/api/condominiums/{$condominium->id}/administrators/{$administrator->id}", [
            'first_name' => 'Administrador Actualizado',
        ], $this->headers('byron_np@hotmail.com'))
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Administrador Actualizado');

        $this->patchJson("/api/condominiums/{$condominium->id}/administrators/{$administrator->id}/status", [
            'is_access_enabled' => false,
        ], $this->headers('byron_np@hotmail.com'))
            ->assertOk()
            ->assertJsonPath('data.is_access_enabled', false);

        $this->deleteJson("/api/condominiums/{$condominium->id}/administrators/{$administrator->id}", [], $this->headers('byron_np@hotmail.com'))
            ->assertOk();

        $this->assertDatabaseMissing('condominium_user', [
            'condominium_id' => $condominium->id,
            'user_id' => $administrator->id,
            'deleted_at' => null,
        ]);
    }

    private function condominium(): Condominium
    {
        return Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
    }

    private function documentType(): CatalogItem
    {
        return CatalogItem::whereHas('catalog', fn ($query) => $query->where('code', 'document_types'))
            ->where('code', 'cedula')
            ->firstOrFail();
    }

    /** @return array<string, string> */
    private function headers(string $email): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $email,
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
