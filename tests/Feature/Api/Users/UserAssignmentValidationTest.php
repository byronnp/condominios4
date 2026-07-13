<?php

namespace Tests\Feature\Api\Users;

use App\Models\CatalogItem;
use App\Models\Condominium;
use App\Models\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAssignmentValidationTest extends TestCase
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

    public function test_user_cannot_assign_role_id_from_another_condominium(): void
    {
        $first = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $second = Condominium::where('slug', 'condominio-jardines-del-valle')->firstOrFail();
        $roleFromSecond = Role::where('condominium_id', $second->id)->where('code', 'residente')->firstOrFail();

        $this->postJson("/api/condominiums/{$first->id}/users", [
            'first_name' => 'Usuario',
            'last_name' => 'Prueba',
            'email' => 'role.mismatch@example.com',
            'country' => 'EC',
            'document_type_id' => $this->passportId(),
            'document_number' => 'PA999999',
            'role_id' => $roleFromSecond->id,
        ], $this->headers('byron_np@hotmail.com'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('assignments.0.role_id');
    }

    private function passportId(): int
    {
        return (int) CatalogItem::whereHas('catalog', fn ($query) => $query->where('code', 'document_types'))
            ->where('code', 'pasaporte')
            ->value('id');
    }

    private function headers(string $email, string $password = 'admin123'): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ])->assertOk();

        return ['Authorization' => 'Bearer '.$response->json('data.access_token')];
    }
}
