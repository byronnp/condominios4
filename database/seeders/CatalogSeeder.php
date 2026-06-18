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
                'code' => 'condominium_features',
                'name' => 'Características de condominio',
                'description' => 'Características principales disponibles en un condominio.',
                'items' => [
                    ['code' => 'seguridad_24_7', 'name' => 'Seguridad 24/7'],
                    ['code' => 'camaras_seguridad', 'name' => 'Cámaras de seguridad'],
                    ['code' => 'control_acceso', 'name' => 'Control de acceso'],
                    ['code' => 'parqueadero_visitas', 'name' => 'Parqueadero de visitas'],
                    ['code' => 'areas_verdes', 'name' => 'Áreas verdes'],
                    ['code' => 'juegos_infantiles', 'name' => 'Juegos infantiles'],
                    ['code' => 'piscina', 'name' => 'Piscina'],
                    ['code' => 'gimnasio', 'name' => 'Gimnasio'],
                    ['code' => 'salon_comunal', 'name' => 'Salón comunal'],
                    ['code' => 'area_bbq', 'name' => 'Área BBQ'],
                    ['code' => 'cancha_deportiva', 'name' => 'Cancha deportiva'],
                    ['code' => 'ascensor', 'name' => 'Ascensor'],
                    ['code' => 'generador_electrico', 'name' => 'Generador eléctrico'],
                    ['code' => 'cisterna', 'name' => 'Cisterna'],
                ],
            ],
            [
                'code' => 'condominium_types',
                'name' => 'Tipos de condominio',
                'description' => 'Clasificación general del condominio o conjunto.',
                'items' => [
                    ['code' => 'residencial', 'name' => 'Residencial'],
                    ['code' => 'comercial', 'name' => 'Comercial'],
                    ['code' => 'mixto', 'name' => 'Mixto'],
                    ['code' => 'conjunto_habitacional', 'name' => 'Conjunto habitacional'],
                    ['code' => 'edificio_departamentos', 'name' => 'Edificio de departamentos'],
                    ['code' => 'urbanizacion_privada', 'name' => 'Urbanización privada'],
                    ['code' => 'oficinas', 'name' => 'Oficinas'],
                    ['code' => 'locales_comerciales', 'name' => 'Locales comerciales'],
                ],
            ],
            [
                'code' => 'resident_relationship_types',
                'name' => 'Tipos de relación con unidad',
                'description' => 'Relaciones posibles entre una persona residente y una unidad.',
                'items' => [
                    ['code' => 'propietario', 'name' => 'Propietario'],
                    ['code' => 'copropietario', 'name' => 'Copropietario'],
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
