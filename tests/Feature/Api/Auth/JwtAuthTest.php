<?php

namespace Tests\Feature\Api\Auth;

use App\Models\Catalog;
use Database\Seeders\CatalogSeeder;
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
            'name' => 'Carlos Perez',
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
            'name' => 'Ana Gomez',
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
            ->assertJsonPath('data.user.email', 'ana@example.com');

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

    private function documentTypeId(): int
    {
        return Catalog::where('code', 'document_types')
            ->firstOrFail()
            ->items()
            ->where('code', 'cedula')
            ->value('id');
    }
}
