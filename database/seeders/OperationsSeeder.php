<?php

namespace Database\Seeders;

use App\Models\CommonArea;
use App\Models\CommonAreaReservation;
use App\Models\Condominium;
use App\Models\Incident;
use App\Models\Maintenance;
use App\Models\Unit;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Database\Seeder;

class OperationsSeeder extends Seeder
{
    public function run(): void
    {
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->first();
        $admin = User::where('email', 'byronnp@gmail.com')->first();
        $house = Unit::where('code', 'CASA-01')->first();

        if (! $condominium || ! $admin || ! $house) {
            return;
        }

        $visitor = Visitor::updateOrCreate([
            'condominium_id' => $condominium->id,
            'document_number' => '1700000001',
        ], [
            'name' => 'Carlos Visitante',
            'phone' => '0991112222',
            'email' => 'carlos.visitante@example.com',
            'notes' => 'Visitante frecuente de prueba.',
            'is_active' => true,
        ]);

        $visit = Visit::updateOrCreate([
            'condominium_id' => $condominium->id,
            'authorization_code' => 'VISITA-JUN',
        ], [
            'unit_id' => $house->id,
            'visitor_id' => $visitor->id,
            'registered_by_user_id' => $admin->id,
            'authorized_by_user_id' => $admin->id,
            'purpose' => 'Entrega de documentos',
            'scheduled_at' => '2026-06-20 10:00:00',
            'valid_until' => '2026-06-20 18:00:00',
            'status' => 'authorized',
            'notes' => 'Visita autorizada por administración.',
        ]);

        $visit->logs()->updateOrCreate([
            'type' => 'entry',
        ], [
            'condominium_id' => $condominium->id,
            'unit_id' => $house->id,
            'visitor_id' => $visitor->id,
            'logged_by_user_id' => $admin->id,
            'logged_at' => '2026-06-20 10:05:00',
            'notes' => 'Ingreso de prueba.',
        ]);

        $area = CommonArea::updateOrCreate([
            'condominium_id' => $condominium->id,
            'code' => 'sala_comunal',
        ], [
            'name' => 'Sala comunal',
            'description' => 'Área social para reuniones y eventos.',
            'capacity' => 40,
            'reservation_fee' => 15.00,
            'requires_approval' => true,
            'is_active' => true,
        ]);

        CommonAreaReservation::updateOrCreate([
            'condominium_id' => $condominium->id,
            'common_area_id' => $area->id,
            'starts_at' => '2026-06-25 18:00:00',
        ], [
            'unit_id' => $house->id,
            'user_id' => $admin->id,
            'ends_at' => '2026-06-25 21:00:00',
            'attendees_count' => 12,
            'total_amount' => 15.00,
            'status' => 'approved',
            'notes' => 'Reserva de prueba aprobada.',
        ]);

        Incident::updateOrCreate([
            'condominium_id' => $condominium->id,
            'title' => 'Luminaria dañada en ingreso',
        ], [
            'unit_id' => null,
            'reported_by_user_id' => $admin->id,
            'assigned_to_user_id' => $admin->id,
            'description' => 'La luminaria principal del ingreso no enciende.',
            'category' => 'security',
            'priority' => 'medium',
            'status' => 'open',
            'occurred_at' => '2026-06-18 19:00:00',
        ]);

        $maintenance = Maintenance::updateOrCreate([
            'condominium_id' => $condominium->id,
            'title' => 'Mantenimiento preventivo de bomba',
        ], [
            'common_area_id' => null,
            'reported_by_user_id' => $admin->id,
            'assigned_to_user_id' => $admin->id,
            'description' => 'Revisión mensual del sistema de bombeo.',
            'type' => 'preventive',
            'priority' => 'high',
            'status' => 'scheduled',
            'scheduled_starts_at' => '2026-06-28 09:00:00',
            'scheduled_ends_at' => '2026-06-28 12:00:00',
            'cost' => 80.00,
        ]);

        $maintenance->tasks()->updateOrCreate([
            'title' => 'Revisar presión de bomba',
        ], [
            'assigned_to_user_id' => $admin->id,
            'description' => 'Validar presión y encendido automático.',
            'status' => 'pending',
            'due_at' => '2026-06-28 12:00:00',
        ]);
    }
}
