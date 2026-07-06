<?php

namespace Tests\Feature\Api\Auth;

use App\Models\AuthSession;
use App\Models\Catalog;
use App\Models\RefreshToken;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwtAuthTest extends TestCase
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

        $this->seed(CatalogSeeder::class);
    }

    public function test_user_can_register_and_receive_tokens(): void
    {
        $documentTypeId = $this->documentTypeId();

        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'Carlos',
            'last_name' => 'Perez',
            'email' => 'carlos@example.com',
            'password' => 'admin123',
            'password_confirmation' => 'admin123',
            'country' => 'EC',
            'document_type_id' => $documentTypeId,
            'document_number' => '0102030405',
            'device_name' => 'Browser',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonMissingPath('data.user')
            ->assertJsonMissingPath('data.auth_session')
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'refresh_token',
                    'expires_in',
                ],
            ]);
    }

    public function test_user_can_login_refresh_and_access_me(): void
    {
        $documentTypeId = $this->documentTypeId();

        $this->postJson('/api/auth/register', [
            'first_name' => 'Ana',
            'last_name' => 'Gomez',
            'email' => 'ana@example.com',
            'password' => 'admin123',
            'password_confirmation' => 'admin123',
            'country' => 'EC',
            'document_type_id' => $documentTypeId,
            'document_number' => '1716128911',
        ])->assertCreated();

        $login = $this->postJson('/api/auth/login', [
            'email' => 'ana@example.com',
            'password' => 'admin123',
        ])->assertOk();

        $accessToken = $login->json('data.access_token');
        $refreshToken = $login->json('data.refresh_token');

        $this->getJson('/api/auth/me', [
            'Authorization' => "Bearer {$accessToken}",
        ])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'ana@example.com')
            ->assertJsonPath('data.user.name', 'Ana Gomez')
            ->assertJsonPath('data.user.first_name', 'Ana')
            ->assertJsonPath('data.user.last_name', 'Gomez')
            ->assertJsonPath('data.platform_role', null)
            ->assertJsonPath('data.is_platform_admin', false)
            ->assertJsonPath('data.permissions', []);

        $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'refresh_token',
                ],
            ]);
    }

    public function test_login_is_rejected_when_user_access_is_disabled(): void
    {
        $user = User::create([
            'first_name' => 'Bloqueado',
            'last_name' => 'Auth',
            'email' => 'bloqueado@example.com',
            'country' => 'EC',
            'document_number' => 'AUTH-001',
            'password' => 'Secret123!',
            'is_access_enabled' => false,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Secret123!',
        ])
            ->assertForbidden()
            ->assertJsonPath('code', 'user_access_disabled');
    }

    public function test_jwt_is_rejected_after_user_is_disabled(): void
    {
        $documentTypeId = $this->documentTypeId();

        $register = $this->postJson('/api/auth/register', [
            'first_name' => 'Ana',
            'last_name' => 'Gomez',
            'email' => 'ana-disabled@example.com',
            'password' => 'admin123',
            'password_confirmation' => 'admin123',
            'country' => 'EC',
            'document_type_id' => $documentTypeId,
            'document_number' => '1716128999',
        ])->assertCreated();

        $accessToken = $register->json('data.access_token');

        $user = User::where('email', 'ana-disabled@example.com')->firstOrFail();
        $user->update(['is_access_enabled' => false]);

        $this->getJson('/api/auth/me', [
            'Authorization' => "Bearer {$accessToken}",
        ])
            ->assertForbidden()
            ->assertJsonPath('code', 'user_access_disabled');
    }

    public function test_refresh_token_cannot_be_reused_after_rotation(): void
    {
        $documentTypeId = $this->documentTypeId();

        $register = $this->postJson('/api/auth/register', [
            'first_name' => 'Refresh',
            'last_name' => 'User',
            'email' => 'refresh@example.com',
            'password' => 'admin123',
            'password_confirmation' => 'admin123',
            'country' => 'EC',
            'document_type_id' => $documentTypeId,
            'document_number' => '1716128000',
        ])->assertCreated();

        $refreshToken = $register->json('data.refresh_token');

        $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])->assertOk();

        $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'refresh_token_revoked');
    }

    public function test_swagger_seed_user_can_login(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->postJson('/api/auth/login', [
            'email' => 'swagger.admin@example.com',
            'password' => 'Swagger123!',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'refresh_token',
                    'expires_in',
                ],
            ]);
    }

    public function test_me_returns_user_roles_for_current_condominium(): void
    {
        $this->seed(DatabaseSeeder::class);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'swagger.admin@example.com',
            'password' => 'Swagger123!',
        ])->assertOk();

        $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer '.$login->json('data.access_token'),
        ])
            ->assertOk()
            ->assertJsonPath('data.condominium.name', 'Condominio Los Cedros')
            ->assertJsonPath('data.is_platform_admin', false)
            ->assertJsonFragment(['code' => 'administrador']);
    }

    public function test_me_returns_platform_role_for_senior_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'byron_np@hotmail.com',
            'password' => 'admin123',
        ])->assertOk();

        $response = $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer '.$login->json('data.access_token'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.platform_role.code', 'administrador_senior')
            ->assertJsonPath('data.platform_role.name', 'Administrador Senior')
            ->assertJsonPath('data.is_platform_admin', true)
            ->assertJsonPath('data.condominium', null)
            ->assertJsonCount(0, 'data.roles')
            ->assertJsonCount(0, 'data.permissions');

        $seniorAdmin = User::where('email', 'byron_np@hotmail.com')->firstOrFail();

        $this->assertDatabaseMissing('condominium_user', [
            'user_id' => $seniorAdmin->id,
        ]);
    }

    public function test_logout_revokes_current_access_token(): void
    {
        $documentTypeId = $this->documentTypeId();

        $register = $this->postJson('/api/auth/register', [
            'name' => 'Luis Mora',
            'email' => 'luis@example.com',
            'password' => 'admin123',
            'password_confirmation' => 'admin123',
            'country' => 'EC',
            'document_type_id' => $documentTypeId,
            'document_number' => '999888777',
        ])->assertCreated();

        $accessToken = $register->json('data.access_token');

        $this->postJson('/api/auth/logout', [], [
            'Authorization' => "Bearer {$accessToken}",
        ])->assertOk();

        $this->getJson('/api/auth/me', [
            'Authorization' => "Bearer {$accessToken}",
        ])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'access_token_revoked');
    }

    public function test_logout_all_revokes_all_active_sessions(): void
    {
        $documentTypeId = $this->documentTypeId();

        $register = $this->postJson('/api/auth/register', [
            'first_name' => 'Multi',
            'last_name' => 'Session',
            'email' => 'multisession@example.com',
            'password' => 'admin123',
            'password_confirmation' => 'admin123',
            'country' => 'EC',
            'document_type_id' => $documentTypeId,
            'document_number' => '1716128111',
        ])->assertCreated();

        $firstAccessToken = $register->json('data.access_token');

        $secondLogin = $this->postJson('/api/auth/login', [
            'email' => 'multisession@example.com',
            'password' => 'admin123',
        ])->assertOk();

        $secondAccessToken = $secondLogin->json('data.access_token');

        $this->postJson('/api/auth/logout-all', [], [
            'Authorization' => "Bearer {$firstAccessToken}",
        ])->assertOk();

        $this->getJson('/api/auth/me', [
            'Authorization' => "Bearer {$secondAccessToken}",
        ])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'session_invalid');
    }

    public function test_legacy_routes_are_not_available(): void
    {
        $this->postJson('/api/login', [])
            ->assertNotFound()
            ->assertJsonPath('code', 'not_found');

        $this->postJson('/api/register', [])
            ->assertNotFound()
            ->assertJsonPath('code', 'not_found');

        $this->postJson('/api/logout', [])
            ->assertNotFound()
            ->assertJsonPath('code', 'not_found');

        $this->getJson('/api/user')
            ->assertNotFound()
            ->assertJsonPath('code', 'not_found');
    }

    public function test_register_is_forbidden_in_production_without_admin_session(): void
    {
        $previousEnvironment = app()->environment();
        app()->detectEnvironment(fn () => 'production');

        try {
            $this->postJson('/api/auth/register', [
                'first_name' => 'Public',
                'last_name' => 'Denied',
                'email' => 'public-denied@example.com',
                'password' => 'admin123',
                'password_confirmation' => 'admin123',
                'country' => 'EC',
                'document_type_id' => $this->documentTypeId(),
                'document_number' => '1716128222',
            ])
                ->assertForbidden();

            $this->assertDatabaseMissing('users', [
                'email' => 'public-denied@example.com',
            ]);
        } finally {
            app()->detectEnvironment(fn () => $previousEnvironment);
        }
    }

    private function documentTypeId(): int
    {
        return Catalog::where('code', 'document_types')
            ->firstOrFail()
            ->items()
            ->where('code', 'cedula')
            ->value('id');
    }
}
