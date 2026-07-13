<?php

namespace Tests\Feature\Api\Users;

use App\Domain\Users\Services\UserInvitationService;
use App\Mail\UserAccessInvitationMail;
use App\Models\CatalogItem;
use App\Models\Condominium;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'jwt.secret' => 'testing-secret-with-at-least-32-chars',
            'jwt.access_ttl_minutes' => 60,
            'jwt.refresh_ttl_days' => 30,
            'invitations.frontend_url' => 'https://frontend.example.com',
        ]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_pending_administrator_can_activate_access_once(): void
    {
        Mail::fake();
        $actor = User::where('email', 'byron_np@hotmail.com')->firstOrFail();
        $condominium = Condominium::firstOrFail();
        $role = Role::where('condominium_id', $condominium->id)->where('code', 'administrador')->firstOrFail();
        $user = User::create([
            'first_name' => 'Invitada', 'last_name' => 'Segura', 'email' => 'invitada@example.com',
            'country' => 'EC', 'document_number' => 'INV-001', 'password' => null, 'is_access_enabled' => false,
        ]);
        $condominium->users()->attach($user->id, ['is_active' => true, 'joined_at' => now()]);

        app(UserInvitationService::class)->invite($user, $condominium, $role, $actor);

        $mail = null;
        Mail::assertQueued(UserAccessInvitationMail::class, function (UserAccessInvitationMail $queued) use (&$mail): bool {
            $mail = $queued;

            return true;
        });

        $this->assertStringContainsString(
            'https://frontend.example.com/#/activar-acceso?token=',
            $mail->render(),
        );

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'ClaveSegura123!'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Credenciales inválidas.');

        $payload = ['token' => $mail->plainToken, 'password' => 'ClaveSegura123!', 'password_confirmation' => 'ClaveSegura123!'];
        $this->postJson('/api/auth/activate-access', $payload)->assertOk();
        $this->postJson('/api/auth/activate-access', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('code', 'access_invitation_used');
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'ClaveSegura123!'])->assertOk();

        $this->assertDatabaseHas('user_access_invitations', ['user_id' => $user->id, 'status' => 'accepted', 'token_hash' => null]);
    }

    public function test_activate_access_rejects_expired_invitation(): void
    {
        Mail::fake();
        $actor = User::where('email', 'byron_np@hotmail.com')->firstOrFail();
        $condominium = Condominium::firstOrFail();
        $role = Role::where('condominium_id', $condominium->id)->where('code', 'administrador')->firstOrFail();
        $user = User::create([
            'first_name' => 'Expirada',
            'last_name' => 'Segura',
            'email' => 'expirada@example.com',
            'country' => 'EC',
            'document_number' => 'INV-EXPIRED',
            'password' => null,
            'is_access_enabled' => false,
        ]);
        $condominium->users()->attach($user->id, ['is_active' => true, 'joined_at' => now()]);

        app(UserInvitationService::class)->invite($user, $condominium, $role, $actor);

        $mail = null;
        Mail::assertQueued(UserAccessInvitationMail::class, function (UserAccessInvitationMail $queued) use (&$mail): bool {
            $mail = $queued;

            return true;
        });

        $user->accessInvitations()->latest('id')->firstOrFail()->update([
            'expires_at' => now()->subMinute(),
        ]);

        $this->postJson('/api/auth/activate-access', [
            'token' => $mail->plainToken,
            'password' => 'ClaveSegura123!',
            'password_confirmation' => 'ClaveSegura123!',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'access_invitation_expired');
    }

    public function test_senior_administrator_can_resend_invitation_and_revoke_previous_one(): void
    {
        Mail::fake();
        $actor = User::where('email', 'byron_np@hotmail.com')->firstOrFail();
        $condominium = Condominium::firstOrFail();
        $role = Role::where('condominium_id', $condominium->id)->where('code', 'administrador')->firstOrFail();
        $documentType = CatalogItem::whereHas('catalog', fn ($query) => $query->where('code', 'document_types'))
            ->where('code', 'cedula')
            ->firstOrFail();
        $user = User::create([
            'first_name' => 'Pendiente',
            'email' => 'pendiente@example.com',
            'country' => 'EC',
            'document_type_id' => $documentType->id,
            'document_number' => '0911111111',
            'password' => null,
            'is_access_enabled' => false,
        ]);
        $condominium->users()->attach($user->id, ['is_active' => true, 'joined_at' => now()]);
        app(UserInvitationService::class)->invite($user, $condominium, $role, $actor);

        $token = $this->postJson('/api/auth/login', ['email' => $actor->email, 'password' => 'admin123'])
            ->assertOk()->json('data.access_token');

        $this->postJson("/api/condominiums/{$condominium->id}/administrators", [
            'first_name' => $user->first_name,
            'email' => $user->email,
            'country' => $user->country,
            'document_type_id' => $user->document_type_id,
            'document_number' => $user->document_number,
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertCreated();

        $this->assertSame(2, $user->accessInvitations()->count());
        $this->assertDatabaseHas('user_access_invitations', ['user_id' => $user->id, 'status' => 'revoked', 'token_hash' => null]);
        $this->assertDatabaseHas('user_access_invitations', ['user_id' => $user->id, 'status' => 'pending']);
        Mail::assertQueued(UserAccessInvitationMail::class, 2);
    }
}
