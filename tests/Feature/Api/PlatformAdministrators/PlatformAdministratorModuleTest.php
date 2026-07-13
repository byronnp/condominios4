<?php

namespace Tests\Feature\Api\PlatformAdministrators;

use App\Mail\UserAccessInvitationMail;
use App\Models\AuthSession;
use App\Models\CatalogItem;
use App\Models\RefreshToken;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserAccessInvitation;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PlatformAdministratorModuleTest extends TestCase
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

    public function test_senior_admin_creates_another_senior_admin(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/platform-administrators', $this->payload(), $this->headers())
            ->assertCreated()
            ->assertJsonPath('data.email', 'ana.senior@example.com')
            ->assertJsonPath('data.platform_role.code', 'administrador_senior')
            ->assertJsonPath('data.is_access_enabled', false)
            ->assertJsonPath('data.access_status', 'pending_activation')
            ->assertJsonPath('data.invitation.status', 'pending');

        $administratorId = (int) $response->json('data.id');

        $this->assertSeniorRoleAssigned($administratorId);
        $this->assertDatabaseMissing('condominium_user', ['user_id' => $administratorId]);

        $this->assertFalse(DB::table('condominium_user_role')
            ->join('condominium_user', 'condominium_user.id', '=', 'condominium_user_role.condominium_user_id')
            ->where('condominium_user.user_id', $administratorId)
            ->exists());

        $invitation = UserAccessInvitation::query()
            ->where('user_id', $administratorId)
            ->whereNull('condominium_id')
            ->firstOrFail();

        Mail::assertQueued(UserAccessInvitationMail::class, fn (UserAccessInvitationMail $mail): bool => $mail->invitation->is($invitation));
    }

    public function test_reuses_existing_user_and_creates_no_duplicate_user(): void
    {
        Mail::fake();

        $existing = User::create([
            'first_name' => 'Ana',
            'last_name' => 'Existente',
            'email' => 'ana.senior@example.com',
            'country' => 'EC',
            'document_type_id' => $this->documentType()->id,
            'document_number' => '0912345678',
            'password' => null,
            'is_access_enabled' => false,
        ]);

        $usersBefore = User::count();

        $this->postJson('/api/platform-administrators', $this->payload(), $this->headers())
            ->assertCreated()
            ->assertJsonPath('data.id', $existing->id);

        $this->assertSame($usersBefore, User::count());
        $this->assertSeniorRoleAssigned($existing->id);
        $this->assertDatabaseMissing('condominium_user', ['user_id' => $existing->id]);
    }

    public function test_creates_new_user_without_condominium_membership(): void
    {
        Mail::fake();

        $this->assertDatabaseMissing('users', ['email' => 'ana.senior@example.com']);

        $response = $this->postJson('/api/platform-administrators', $this->payload(), $this->headers())
            ->assertCreated();

        $administratorId = (int) $response->json('data.id');

        $this->assertDatabaseHas('users', [
            'id' => $administratorId,
            'email' => 'ana.senior@example.com',
            'password' => null,
            'is_access_enabled' => false,
        ]);
        $this->assertDatabaseMissing('condominium_user', ['user_id' => $administratorId]);
    }

    public function test_does_not_duplicate_senior_role_assignment(): void
    {
        Mail::fake();

        $first = $this->postJson('/api/platform-administrators', $this->payload(), $this->headers())
            ->assertCreated();

        $administratorId = (int) $first->json('data.id');

        $this->postJson('/api/platform-administrators', $this->payload(), $this->headers())
            ->assertOk()
            ->assertJsonPath('message', 'El usuario ya es administrador de plataforma.')
            ->assertJsonPath('data.id', $administratorId);

        $this->assertSame(1, $this->seniorRoleAssignmentCount($administratorId));
    }

    public function test_condominium_admin_receives_403(): void
    {
        $this->postJson('/api/platform-administrators', $this->payload(), $this->headers('byronnp@gmail.com', 'admin123'))
            ->assertForbidden();
    }

    public function test_active_user_does_not_receive_invitation(): void
    {
        Mail::fake();

        $existing = User::create([
            'first_name' => 'Ana',
            'last_name' => 'Activa',
            'email' => 'ana.senior@example.com',
            'country' => 'EC',
            'document_type_id' => $this->documentType()->id,
            'document_number' => '0912345678',
            'password' => 'Secret123!',
            'is_access_enabled' => true,
        ]);

        $this->postJson('/api/platform-administrators', $this->payload(), $this->headers())
            ->assertCreated()
            ->assertJsonPath('data.id', $existing->id)
            ->assertJsonPath('data.is_access_enabled', true)
            ->assertJsonPath('data.invitation', null);

        $this->assertDatabaseMissing('user_access_invitations', [
            'user_id' => $existing->id,
            'condominium_id' => null,
        ]);
        Mail::assertNothingQueued();
    }

    public function test_inactive_user_receives_platform_invitation(): void
    {
        Mail::fake();

        $existing = User::create([
            'first_name' => 'Ana',
            'last_name' => 'Inactiva',
            'email' => 'ana.senior@example.com',
            'country' => 'EC',
            'document_type_id' => $this->documentType()->id,
            'document_number' => '0912345678',
            'password' => null,
            'is_access_enabled' => false,
        ]);

        $this->postJson('/api/platform-administrators', $this->payload(), $this->headers())
            ->assertCreated()
            ->assertJsonPath('data.id', $existing->id)
            ->assertJsonPath('data.invitation.status', 'pending');

        $invitation = UserAccessInvitation::query()
            ->where('user_id', $existing->id)
            ->whereNull('condominium_id')
            ->firstOrFail();

        $this->assertSame(UserAccessInvitation::STATUS_PENDING, $invitation->status);
        $this->assertNotNull($invitation->token_hash);
        Mail::assertQueued(UserAccessInvitationMail::class, fn (UserAccessInvitationMail $mail): bool => $mail->invitation->is($invitation));
    }

    public function test_delete_removes_only_global_role_and_keeps_user(): void
    {
        $administrator = $this->createPlatformAdministrator();

        $this->deleteJson("/api/platform-administrators/{$administrator->id}", [], $this->headers())
            ->assertOk()
            ->assertJsonPath('message', 'Administrador de plataforma eliminado correctamente.');

        $this->assertDatabaseHas('users', ['id' => $administrator->id]);
        $this->assertSame(0, $this->seniorRoleAssignmentCount($administrator->id));
        $this->assertDatabaseMissing('condominium_user', ['user_id' => $administrator->id]);
    }

    public function test_disabling_platform_admin_revokes_sessions_and_refresh_tokens(): void
    {
        $administrator = $this->createPlatformAdministrator(isAccessEnabled: true);
        $actor = User::where('email', 'byron_np@hotmail.com')->firstOrFail();

        $session = AuthSession::create([
            'user_id' => $administrator->id,
            'started_at' => now()->subHour(),
            'last_activity_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Feature Test',
            'device_name' => 'Browser',
            'login_method' => 'password',
            'is_active' => true,
        ]);

        $refreshToken = RefreshToken::create([
            'user_id' => $administrator->id,
            'auth_session_id' => $session->id,
            'token_hash' => hash('sha256', 'refresh-token'),
            'expires_at' => now()->addDay(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Feature Test',
        ]);

        $this->patchJson("/api/platform-administrators/{$administrator->id}/status", [
            'is_access_enabled' => false,
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('data.is_access_enabled', false);

        $this->assertDatabaseHas('users', [
            'id' => $administrator->id,
            'is_access_enabled' => false,
        ]);

        $this->assertDatabaseHas('auth_sessions', [
            'id' => $session->id,
            'is_active' => false,
            'logout_reason' => 'platform_admin_disabled',
        ]);

        $this->assertDatabaseHas('refresh_tokens', [
            'id' => $refreshToken->id,
            'revoked_by_user_id' => $actor->id,
            'revoke_reason' => 'platform_admin_disabled',
        ]);

        $this->assertNotNull($refreshToken->fresh()->revoked_at);
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'first_name' => 'Ana',
            'last_name' => 'Senior',
            'email' => 'ana.senior@example.com',
            'country' => 'EC',
            'document_type_id' => $this->documentType()->id,
            'document_number' => '0912345678',
            'phone' => '0991234567',
        ];
    }

    /** @return array<string, string> */
    private function headers(string $email = 'byron_np@hotmail.com', string $password = 'admin123'): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ])->assertOk();

        return ['Authorization' => 'Bearer '.$response->json('data.access_token')];
    }

    private function documentType(): CatalogItem
    {
        return CatalogItem::whereHas('catalog', fn ($query) => $query->where('code', 'document_types'))
            ->where('code', 'cedula')
            ->firstOrFail();
    }

    private function createPlatformAdministrator(bool $isAccessEnabled = false): User
    {
        $administrator = User::create([
            'first_name' => 'Carlos',
            'last_name' => 'Senior',
            'email' => 'carlos.senior@example.com',
            'country' => 'EC',
            'document_type_id' => $this->documentType()->id,
            'document_number' => '1716128999',
            'password' => $isAccessEnabled ? 'Secret123!' : null,
            'is_access_enabled' => $isAccessEnabled,
        ]);

        $role = Role::where('code', 'administrador_senior')
            ->whereNull('condominium_id')
            ->firstOrFail();
        $tenant = Tenant::where('slug', 'admin-platform')->firstOrFail();

        DB::table('role_user')->insert([
            'role_id' => $role->id,
            'user_id' => $administrator->id,
            'tenant_id' => $tenant->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $administrator;
    }

    private function assertSeniorRoleAssigned(int $userId): void
    {
        $this->assertSame(1, $this->seniorRoleAssignmentCount($userId));
    }

    private function seniorRoleAssignmentCount(int $userId): int
    {
        return DB::table('role_user')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('role_user.user_id', $userId)
            ->whereNull('roles.condominium_id')
            ->where('roles.code', 'administrador_senior')
            ->count();
    }
}
