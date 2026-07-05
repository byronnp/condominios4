<?php

namespace Tests\Feature\Api\Units;

use App\Models\Catalog;
use App\Models\Condominium;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitPhaseTest extends TestCase
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

    public function test_phase_five_seeders_create_units_people_billing_profiles_aliquots_and_invitations(): void
    {
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $house = Unit::where('code', 'CASA-01')->firstOrFail();

        $this->assertDatabaseHas('condominium_blocks', ['condominium_id' => $condominium->id, 'code' => 'TORRE-A']);
        $this->assertDatabaseHas('units', ['condominium_id' => $condominium->id, 'code' => 'P-12', 'parent_unit_id' => $house->id]);
        $this->assertDatabaseHas('unit_aliquots', ['unit_id' => $house->id, 'period_year' => 2026, 'period_month' => 6]);
        $this->assertDatabaseHas('user_billing_profiles', ['business_name' => 'ADMINISTRADOR CONDOMINIO']);
        $this->assertDatabaseHas('user_access_invitations', ['email' => 'ana.perez@example.com']);
        $this->assertDatabaseHas('permissions', ['code' => 'unit_users.manage_all']);
    }

    public function test_admin_can_list_people_change_billing_responsible_and_create_access_invitation(): void
    {
        $token = $this->loginToken();
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $house = Unit::where('code', 'CASA-01')->firstOrFail();
        $tenant = User::where('document_number', '1723456789')->firstOrFail();

        $this->getJson("/api/condominiums/{$condominium->id}/units/{$house->id}/users", [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonFragment(['relationship_code' => 'propietario'])
            ->assertJsonFragment(['relationship_code' => 'inquilino']);

        $this->patchJson("/api/condominiums/{$condominium->id}/units/{$house->id}/billing-responsible", [
            'user_id' => $tenant->id,
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $tenant->id,
                'is_billing_responsible' => 1,
            ]);

        $invitation = $this->postJson("/api/condominiums/{$condominium->id}/units/{$house->id}/users/{$tenant->id}/access-invitations", [
            'email' => 'juan.perez@example.com',
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'juan.perez@example.com');

        $this->postJson("/api/access-invitations/{$invitation->json('data.token')}/accept", [
            'password' => 'Admin123!',
            'password_confirmation' => 'Admin123!',
        ])->assertOk();

        $this->postJson('/api/auth/login', [
            'email' => 'juan.perez@example.com',
            'password' => 'Admin123!',
        ])->assertOk();
    }

    public function test_senior_administrator_can_assign_an_owner_to_any_condominium_house(): void
    {
        $token = $this->loginToken();
        $condominium = Condominium::where('slug', 'condominio-altos-del-bosque')->firstOrFail();
        $house = $condominium->units()->where('code', 'CASA-01')->firstOrFail();
        $documentTypeId = Catalog::where('code', 'document_types')->firstOrFail()->items()->where('code', 'cedula')->value('id');
        $relationshipTypeId = Catalog::where('code', 'resident_relationship_types')->firstOrFail()->items()->where('code', 'propietario')->value('id');

        $this->postJson("/api/condominiums/{$condominium->id}/units/{$house->id}/users", [
            'name' => 'Viviana Castro',
            'first_name' => 'Viviana',
            'last_name' => 'Castro',
            'country' => 'EC',
            'document_type_id' => $documentTypeId,
            'document_number' => '1478523655',
            'phone' => '0987654321',
            'relationship_type_id' => $relationshipTypeId,
            'started_at' => '2026-07-04',
            'is_primary' => true,
            'is_billing_responsible' => true,
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonPath('data.user.document_number', '1478523655')
            ->assertJsonPath('data.unit_relation.relationship_code', 'propietario');
    }

    public function test_unit_can_be_created_with_initial_aliquot(): void
    {
        $token = $this->loginToken();
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $unitTypeId = Catalog::where('code', 'unit_types')->firstOrFail()->items()->where('code', 'casa')->value('id');

        $this->postJson("/api/condominiums/{$condominium->id}/units", [
            'unit_type_id' => $unitTypeId,
            'code' => 'CASA-02',
            'number' => '02',
            'area_m2' => 110.00,
            'current_aliquot_percentage' => 4.75,
            'aliquot_starts_on' => '2026-07-01',
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'CASA-02')
            ->assertJsonFragment(['period_month' => 7]);
    }

    public function test_authenticated_user_can_get_a_house_by_its_id(): void
    {
        $token = $this->loginToken();
        $house = Unit::where('code', 'CASA-01')->firstOrFail();

        $this->getJson("/api/units/{$house->id}", [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Unidad encontrada.')
            ->assertJsonPath('data.id', $house->id)
            ->assertJsonPath('data.code', 'CASA-01')
            ->assertJsonPath('data.condominium.id', $house->condominium_id);
    }

    public function test_house_by_id_requires_authentication_and_reports_unknown_ids(): void
    {
        $house = Unit::where('code', 'CASA-01')->firstOrFail();

        $this->getJson("/api/units/{$house->id}")->assertUnauthorized();

        $token = $this->loginToken();

        $this->getJson('/api/units/999999', [
            'Authorization' => "Bearer {$token}",
        ])->assertNotFound();
    }

    public function test_units_can_be_paginated_on_the_server(): void
    {
        $token = $this->loginToken();
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();

        $this->getJson("/api/condominiums/{$condominium->id}/units?page=1&per_page=2", [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonStructure([
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    public function test_unit_index_reports_invalid_pagination_parameters(): void
    {
        $token = $this->loginToken();
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();

        $this->getJson("/api/condominiums/{$condominium->id}/units?page=0&per_page=101", [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.page.0', 'La página debe ser al menos 1.')
            ->assertJsonPath(
                'errors.per_page.0',
                'La cantidad de registros por página no puede ser mayor que 100.',
            );
    }

    public function test_person_without_access_cannot_login_until_invitation_is_accepted(): void
    {
        $tenant = User::where('document_number', '1723456789')->firstOrFail();
        $this->assertFalse($tenant->is_access_enabled);
        $this->assertNull($tenant->email);
        $this->assertNull($tenant->password);

        $this->postJson('/api/auth/login', [
            'email' => 'noexiste@example.com',
            'password' => 'admin123',
        ])->assertUnauthorized();
    }

    private function loginToken(): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'byron_np@hotmail.com',
            'password' => 'admin123',
        ])->assertOk();

        return $response->json('data.access_token');
    }
}
