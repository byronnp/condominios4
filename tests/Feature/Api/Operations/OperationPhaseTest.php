<?php

namespace Tests\Feature\Api\Operations;

use App\Models\CommonArea;
use App\Models\Condominium;
use App\Models\Maintenance;
use App\Models\Unit;
use App\Models\Visit;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationPhaseTest extends TestCase
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

    public function test_phase_seven_seeders_create_operation_data(): void
    {
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();

        $this->assertDatabaseHas('visitors', ['condominium_id' => $condominium->id, 'document_number' => '1700000001']);
        $this->assertDatabaseHas('visits', ['condominium_id' => $condominium->id, 'authorization_code' => 'VISITA-JUN']);
        $this->assertDatabaseHas('visit_logs', ['type' => 'entry']);
        $this->assertDatabaseHas('common_areas', ['code' => 'sala_comunal']);
        $this->assertDatabaseHas('common_area_reservations', ['status' => 'approved']);
        $this->assertDatabaseHas('incidents', ['title' => 'Luminaria dañada en ingreso']);
        $this->assertDatabaseHas('maintenances', ['title' => 'Mantenimiento preventivo de bomba']);
        $this->assertDatabaseHas('maintenance_tasks', ['title' => 'Revisar presión de bomba']);
    }

    public function test_admin_can_register_visit_authorize_and_log_entry_exit(): void
    {
        $token = $this->loginToken();
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $unit = Unit::where('code', 'CASA-01')->firstOrFail();

        $visitor = $this->postJson("/api/condominiums/{$condominium->id}/visitors", [
            'name' => 'María Proveedora',
            'document_number' => '1700000002',
            'phone' => '0993334444',
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'María Proveedora');

        $visit = $this->postJson("/api/condominiums/{$condominium->id}/visits", [
            'unit_id' => $unit->id,
            'visitor_id' => $visitor->json('data.id'),
            'purpose' => 'Mantenimiento de internet',
            'scheduled_at' => '2026-07-01 09:00:00',
            'valid_until' => '2026-07-01 12:00:00',
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $visitId = $visit->json('data.id');

        $this->patchJson("/api/condominiums/{$condominium->id}/visits/{$visitId}/authorize", [], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'authorized');

        $this->postJson("/api/condominiums/{$condominium->id}/visits/{$visitId}/entry", [
            'logged_at' => '2026-07-01 09:05:00',
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'entry');

        $this->postJson("/api/condominiums/{$condominium->id}/visits/{$visitId}/exit", [
            'logged_at' => '2026-07-01 10:30:00',
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'exit');

        $this->assertSame('completed', Visit::findOrFail($visitId)->status);
    }

    public function test_common_area_reservation_validates_availability(): void
    {
        $token = $this->loginToken();
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $unit = Unit::where('code', 'CASA-01')->firstOrFail();

        $area = $this->postJson("/api/condominiums/{$condominium->id}/common-areas", [
            'name' => 'Terraza',
            'capacity' => 20,
            'reservation_fee' => 10,
            'is_reservable' => true,
            'requires_approval' => false,
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'terraza')
            ->assertJsonPath('data.is_reservable', true);

        $this->postJson("/api/condominiums/{$condominium->id}/common-area-reservations", [
            'common_area_id' => $area->json('data.id'),
            'unit_id' => $unit->id,
            'starts_at' => '2026-07-02 18:00:00',
            'ends_at' => '2026-07-02 20:00:00',
            'attendees_count' => 8,
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved');

        $this->postJson("/api/condominiums/{$condominium->id}/common-area-reservations", [
            'common_area_id' => $area->json('data.id'),
            'unit_id' => $unit->id,
            'starts_at' => '2026-07-02 19:00:00',
            'ends_at' => '2026-07-02 21:00:00',
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'reservation_overlap');
    }

    public function test_non_reservable_common_area_rejects_reservations(): void
    {
        $token = $this->loginToken();
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $unit = Unit::where('code', 'CASA-01')->firstOrFail();

        $area = $this->postJson("/api/condominiums/{$condominium->id}/common-areas", [
            'name' => 'Garita principal',
            'is_reservable' => false,
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertCreated();

        $this->postJson("/api/condominiums/{$condominium->id}/common-area-reservations", [
            'common_area_id' => $area->json('data.id'),
            'unit_id' => $unit->id,
            'starts_at' => '2026-07-03 08:00:00',
            'ends_at' => '2026-07-03 09:00:00',
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.common_area_id.0',
                'El área común no existe, está inactiva o no admite reservas.',
            );
    }

    public function test_incidents_and_maintenances_can_be_created(): void
    {
        $token = $this->loginToken();
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->firstOrFail();
        $unit = Unit::where('code', 'CASA-01')->firstOrFail();
        $area = CommonArea::where('code', 'sala_comunal')->firstOrFail();

        $this->postJson("/api/condominiums/{$condominium->id}/incidents", [
            'unit_id' => $unit->id,
            'title' => 'Ruido fuera de horario',
            'description' => 'Reporte de ruido después de las 23h00.',
            'priority' => 'high',
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open');

        $maintenance = $this->postJson("/api/condominiums/{$condominium->id}/maintenances", [
            'common_area_id' => $area->id,
            'title' => 'Pintura de sala comunal',
            'type' => 'corrective',
            'scheduled_starts_at' => '2026-07-03 08:00:00',
            'tasks' => [
                ['title' => 'Comprar pintura'],
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonFragment(['title' => 'Comprar pintura']);

        $taskId = Maintenance::findOrFail($maintenance->json('data.id'))->tasks()->firstOrFail()->id;

        $this->patchJson("/api/condominiums/{$condominium->id}/maintenances/{$maintenance->json('data.id')}/tasks/{$taskId}/complete", [], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');
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
