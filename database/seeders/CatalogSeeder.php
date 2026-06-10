<?php

namespace Database\Seeders;

use App\Models\Catalog;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Seed the system catalogs used by forms and business rules.
     */
    public function run(): void
    {
        foreach ($this->catalogs() as $catalogData) {
            $catalog = Catalog::updateOrCreate([
                'code' => $catalogData['code'],
            ], [
                'name' => $catalogData['name'],
                'description' => $catalogData['description'] ?? null,
                'is_system' => true,
                'is_active' => true,
            ]);

            foreach ($catalogData['items'] as $index => $item) {
                $catalog->items()->updateOrCreate([
                    'code' => $item['code'],
                ], [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'sort_order' => $item['sort_order'] ?? $index + 1,
                    'metadata' => $item['metadata'] ?? null,
                    'is_system' => true,
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalogs(): array
    {
        return [
            [
                'code' => 'document_types',
                'name' => 'Tipos de identificación',
                'description' => 'Tipos de documentos permitidos para identificar usuarios y residentes.',
                'items' => [
                    ['code' => 'cedula', 'name' => 'Cédula'],
                    ['code' => 'ruc', 'name' => 'RUC'],
                    ['code' => 'pasaporte', 'name' => 'Pasaporte'],
                ],
            ],
            [
                'code' => 'unit_types',
                'name' => 'Tipos de unidad',
                'description' => 'Clasificación de unidades dentro de un condominio.',
                'items' => [
                    ['code' => 'apartamento', 'name' => 'Apartamento'],
                    ['code' => 'casa', 'name' => 'Casa'],
                    ['code' => 'oficina', 'name' => 'Oficina'],
                    ['code' => 'local_comercial', 'name' => 'Local comercial'],
                    ['code' => 'parqueadero', 'name' => 'Parqueadero'],
                    ['code' => 'bodega', 'name' => 'Bodega'],
                ],
            ],
            [
                'code' => 'resident_relationship_types',
                'name' => 'Tipos de relación con unidad',
                'description' => 'Relaciones posibles entre una persona residente y una unidad.',
                'items' => [
                    ['code' => 'propietario', 'name' => 'Propietario'],
                    ['code' => 'inquilino', 'name' => 'Inquilino'],
                    ['code' => 'ocupante', 'name' => 'Ocupante'],
                    ['code' => 'familiar', 'name' => 'Familiar'],
                    ['code' => 'autorizado', 'name' => 'Autorizado'],
                ],
            ],
            [
                'code' => 'payment_methods',
                'name' => 'Métodos de pago',
                'description' => 'Métodos permitidos para registrar pagos.',
                'items' => [
                    ['code' => 'efectivo', 'name' => 'Efectivo'],
                    ['code' => 'transferencia', 'name' => 'Transferencia bancaria'],
                    ['code' => 'deposito', 'name' => 'Depósito bancario'],
                    ['code' => 'tarjeta', 'name' => 'Tarjeta'],
                    ['code' => 'cheque', 'name' => 'Cheque'],
                ],
            ],
            [
                'code' => 'fee_statuses',
                'name' => 'Estados de cuota',
                'description' => 'Estados posibles de una cuota o valor pendiente.',
                'items' => [
                    ['code' => 'pendiente', 'name' => 'Pendiente'],
                    ['code' => 'parcial', 'name' => 'Parcial'],
                    ['code' => 'pagado', 'name' => 'Pagado'],
                    ['code' => 'vencido', 'name' => 'Vencido'],
                    ['code' => 'anulado', 'name' => 'Anulado'],
                ],
            ],
            [
                'code' => 'incident_statuses',
                'name' => 'Estados de incidencia',
                'description' => 'Estados operativos para solicitudes e incidencias.',
                'items' => [
                    ['code' => 'abierta', 'name' => 'Abierta'],
                    ['code' => 'en_revision', 'name' => 'En revisión'],
                    ['code' => 'en_proceso', 'name' => 'En proceso'],
                    ['code' => 'resuelta', 'name' => 'Resuelta'],
                    ['code' => 'cerrada', 'name' => 'Cerrada'],
                    ['code' => 'rechazada', 'name' => 'Rechazada'],
                ],
            ],
            [
                'code' => 'reservation_statuses',
                'name' => 'Estados de reserva',
                'description' => 'Estados posibles para reservas de zonas comunes.',
                'items' => [
                    ['code' => 'pendiente', 'name' => 'Pendiente'],
                    ['code' => 'aprobada', 'name' => 'Aprobada'],
                    ['code' => 'rechazada', 'name' => 'Rechazada'],
                    ['code' => 'cancelada', 'name' => 'Cancelada'],
                    ['code' => 'finalizada', 'name' => 'Finalizada'],
                ],
            ],
        ];
    }
}
