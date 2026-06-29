<?php

namespace Tests\Feature\Api\Users;

use App\Domain\Users\Services\UserInvitationService;
use App\Mail\UserAccessInvitationMail;
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
            ->assertJsonPath('message', 'Tu acceso aún no ha sido activado. Revisa tu correo de invitación.');

        $payload = ['token' => $mail->plainToken, 'password' => 'ClaveSegura123!', 'password_confirmation' => 'ClaveSegura123!'];
        $this->postJson('/api/auth/activate-access', $payload)->assertOk();
        $this->postJson('/api/auth/activate-access', $payload)->assertUnprocessable();
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'ClaveSegura123!'])->assertOk();

        $this->assertDatabaseHas('user_access_invitations', ['user_id' => $user->id, 'status' => 'accepted', 'token_hash' => null]);
    }

    public function test_senior_administrator_can_resend_invitation_and_revoke_previous_one(): void
    {
        Mail::fake();
        $actor = User::where('email', 'byron_np@hotmail.com')->firstOrFail();
        $condominium = Condominium::firstOrFail();
        $role = Role::where('condominium_id', $condominium->id)->where('code', 'administrador')->firstOrFail();
        $user = User::create([
            'first_name' => 'Pendiente', 'email' => 'pendiente@example.com', 'country' => 'EC',
            'document_number' => 'INV-002', 'password' => null, 'is_access_enabled' => false,
        ]);
        $condominium->users()->attach($user->id, ['is_active' => true, 'joined_at' => now()]);
        app(UserInvitationService::class)->invite($user, $condominium, $role, $actor);

        $token = $this->postJson('/api/auth/login', ['email' => $actor->email, 'password' => 'admin123'])
            ->assertOk()->json('data.access_token');

        $this->postJson("/api/users/{$user->id}/resend-invitation", ['condominium_id' => $condominium->id], [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();

        $this->assertSame(2, $user->accessInvitations()->count());
        $this->assertDatabaseHas('user_access_invitations', ['user_id' => $user->id, 'status' => 'revoked', 'token_hash' => null]);
        $this->assertDatabaseHas('user_access_invitations', ['user_id' => $user->id, 'status' => 'pending']);
        Mail::assertQueued(UserAccessInvitationMail::class, 2);
    }
}
