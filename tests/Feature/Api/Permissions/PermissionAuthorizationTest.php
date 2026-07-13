<?php

namespace Tests\Feature\Api\Permissions;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionAuthorizationTest extends TestCase
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

    public function test_get_permissions_requires_senior_administrator(): void
    {
        $this->getJson('/api/permissions', $this->headers('swagger.admin@example.com'))
            ->assertForbidden();
    }

    public function test_post_permissions_requires_senior_administrator(): void
    {
        $this->postJson('/api/permissions', [
            'module' => 'roles',
            'action' => 'audit',
            'name' => 'Auditar roles',
            'code' => 'roles.audit',
            'description' => 'Permite auditar roles',
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
