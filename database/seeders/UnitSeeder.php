<?php

namespace Database\Seeders;

use App\Models\Catalog;
use App\Models\Condominium;
use App\Models\CondominiumBlock;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $condominium = Condominium::where('slug', 'condominio-los-cedros')->first();
        $documentTypes = Catalog::where('code', 'document_types')->first()?->items()->get()->keyBy('code');
        $unitTypes = Catalog::where('code', 'unit_types')->first()?->items()->get()->keyBy('code');
        $relationships = Catalog::where('code', 'resident_relationship_types')->first()?->items()->get()->keyBy('code');

        if (! $condominium || ! $documentTypes || ! $unitTypes || ! $relationships) {
            return;
        }

        $block = CondominiumBlock::updateOrCreate([
            'condominium_id' => $condominium->id,
            'code' => 'TORRE-A',
        ], [
            'name' => 'Torre A',
            'description' => 'Bloque de prueba para condominios con torres.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $owner = User::where('email', 'byronnp@gmail.com')->firstOrFail();
        $owner->update([
            'phone' => '0999999999',
            'is_access_enabled' => true,
        ]);

        $coOwner = $this->person('1712345678', [
            'name' => 'MARIA FERNANDA GOMEZ RUIZ',
            'first_name' => 'MARIA FERNANDA',
            'last_name' => 'GOMEZ RUIZ',
            'phone' => '0988888888',
        ], $documentTypes->get('cedula')->id);

        $tenant = $this->person('1723456789', [
            'name' => 'JUAN CARLOS PEREZ LOPEZ',
            'first_name' => 'JUAN CARLOS',
            'last_name' => 'PEREZ LOPEZ',
            'phone' => '0977777777',
        ], $documentTypes->get('cedula')->id);

        $authorized = $this->person('1734567890', [
            'name' => 'ANA LUCIA PEREZ MORA',
            'first_name' => 'ANA LUCIA',
            'last_name' => 'PEREZ MORA',
            'phone' => '0966666666',
        ], $documentTypes->get('cedula')->id);

        $house = Unit::updateOrCreate([
            'condominium_id' => $condominium->id,
            'code' => 'CASA-01',
        ], [
            'condominium_block_id' => null,
            'parent_unit_id' => null,
            'unit_type_id' => $unitTypes->get('casa')->id,
            'number' => '01',
            'floor' => null,
            'area_m2' => 120.00,
            'current_aliquot_percentage' => 5.0000,
            'is_assignable' => true,
            'is_active' => true,
        ]);

        Unit::updateOrCreate([
            'condominium_id' => $condominium->id,
            'code' => 'P-12',
        ], [
            'condominium_block_id' => null,
            'parent_unit_id' => $house->id,
            'unit_type_id' => $unitTypes->get('parqueadero')->id,
            'number' => '12',
            'area_m2' => 12.50,
            'current_aliquot_percentage' => 0,
            'is_assignable' => true,
            'is_active' => true,
        ]);

        Unit::updateOrCreate([
            'condominium_id' => $condominium->id,
            'code' => 'A-101',
        ], [
            'condominium_block_id' => $block->id,
            'parent_unit_id' => null,
            'unit_type_id' => $unitTypes->get('apartamento')->id,
            'number' => '101',
            'floor' => '1',
            'area_m2' => 80.50,
            'current_aliquot_percentage' => 3.5000,
            'is_assignable' => true,
            'is_active' => true,
        ]);

        $this->attachPerson($house, $owner, $relationships->get('propietario')->id, true, true, '2026-01-01');
        $this->attachPerson($house, $coOwner, $relationships->get('copropietario')->id, false, false, '2026-01-01');
        $this->attachPerson($house, $tenant, $relationships->get('inquilino')->id, true, false, '2026-06-01', '2027-05-31');
        $this->attachPerson($house, $authorized, $relationships->get('familiar')->id, false, false, '2026-06-10');

        $this->billingProfile($owner, $documentTypes->get('cedula')->id, true);
        $this->billingProfile($tenant, $documentTypes->get('cedula')->id, true);

        $this->seedTestHouses(
            $documentTypes->get('cedula')->id,
            $unitTypes->get('casa')->id,
            $unitTypes->get('parqueadero')->id,
            $relationships->get('propietario')->id,
        );

        $house->aliquots()->updateOrCreate([
            'period_year' => 2026,
            'period_month' => 6,
        ], [
            'percentage' => 5.0000,
            'amount' => 50.00,
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-06-30',
            'status' => 'active',
            'notes' => 'Alícuota inicial de prueba.',
            'is_active' => true,
        ]);

        $token = 'fase5-invitacion-prueba';
        DB::table('user_access_invitations')->updateOrInsert([
            'token_hash' => hash('sha256', $token),
        ], [
            'condominium_id' => $condominium->id,
            'unit_id' => $house->id,
            'user_id' => $authorized->id,
            'invited_by_user_id' => $owner->id,
            'email' => 'ana.perez@example.com',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
            'accepted_at' => null,
            'cancelled_at' => null,
            'cancelled_by_user_id' => null,
            'cancel_reason' => null,
            'deleted_at' => null,
        ]);
    }

    private function seedTestHouses(int $documentTypeId, int $houseTypeId, int $parkingTypeId, int $ownerRelationshipTypeId): void
    {
        $condominiums = Condominium::query()
            ->whereIn('slug', [
                'condominio-jardines-del-valle',
                'condominio-altos-del-bosque',
                'condominio-villas-del-sol',
            ])
            ->orderBy('id')
            ->get();

        foreach ($condominiums as $condominiumIndex => $condominium) {
            for ($houseNumber = 1; $houseNumber <= 5; $houseNumber++) {
                $owner = $this->person(sprintf('18%02d%06d', $condominiumIndex + 1, $houseNumber), [
                    'name' => "PROPIETARIO CASA {$houseNumber} {$condominium->name}",
                    'first_name' => "PROPIETARIO CASA {$houseNumber}",
                    'last_name' => $condominium->name,
                    'phone' => sprintf('097%07d', (($condominiumIndex + 1) * 10) + $houseNumber),
                ], $documentTypeId);

                $house = Unit::updateOrCreate([
                    'condominium_id' => $condominium->id,
                    'code' => sprintf('CASA-%02d', $houseNumber),
                ], [
                    'condominium_block_id' => null,
                    'parent_unit_id' => null,
                    'unit_type_id' => $houseTypeId,
                    'number' => sprintf('%02d', $houseNumber),
                    'floor' => null,
                    'area_m2' => 100 + ($houseNumber * 5),
                    'current_aliquot_percentage' => 20.0000,
                    'is_assignable' => true,
                    'is_active' => true,
                ]);

                Unit::updateOrCreate([
                    'condominium_id' => $condominium->id,
                    'code' => sprintf('P-%02d', $houseNumber),
                ], [
                    'condominium_block_id' => null,
                    'parent_unit_id' => $house->id,
                    'unit_type_id' => $parkingTypeId,
                    'number' => sprintf('%02d', $houseNumber),
                    'floor' => null,
                    'area_m2' => 12.50,
                    'current_aliquot_percentage' => 0,
                    'is_assignable' => true,
                    'is_active' => true,
                ]);

                $this->attachPerson($house, $owner, $ownerRelationshipTypeId, true, true, '2026-01-01');
                $this->billingProfile($owner, $documentTypeId, true);
            }
        }
    }

    private function person(string $documentNumber, array $data, int $documentTypeId): User
    {
        return User::updateOrCreate([
            'country' => 'EC',
            'document_type_id' => $documentTypeId,
            'document_number' => $documentNumber,
        ], [
            'name' => $data['name'],
            'first_name' => $data['first_name'] ?? $data['name'],
            'last_name' => $data['last_name'] ?? null,
            'email' => null,
            'password' => null,
            'phone' => $data['phone'],
            'secondary_phone' => null,
            'is_access_enabled' => false,
        ]);
    }

    private function attachPerson(Unit $unit, User $user, int $relationshipTypeId, bool $isPrimary, bool $isBillingResponsible, string $startedAt, ?string $endedAt = null): void
    {
        if ($isBillingResponsible) {
            DB::table('unit_user')
                ->where('unit_id', $unit->id)
                ->update(['is_billing_responsible' => false]);
        }

        DB::table('unit_user')->updateOrInsert([
            'unit_id' => $unit->id,
            'user_id' => $user->id,
            'relationship_type_id' => $relationshipTypeId,
        ], [
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'is_primary' => $isPrimary,
            'is_billing_responsible' => $isBillingResponsible,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function billingProfile(User $user, int $documentTypeId, bool $isDefault): void
    {
        if ($isDefault) {
            $user->billingProfiles()->update(['is_default' => false]);
        }

        $user->billingProfiles()->updateOrCreate([
            'document_type_id' => $documentTypeId,
            'document_number' => $user->document_number,
        ], [
            'business_name' => $user->name,
            'trade_name' => null,
            'billing_email' => $user->email ?: Str::of($user->document_number)->append('@facturacion.local')->toString(),
            'phone' => $user->phone,
            'address' => 'Quito',
            'city' => 'Quito',
            'province' => 'Pichincha',
            'country' => 'EC',
            'is_default' => $isDefault,
            'is_active' => true,
        ]);
    }
}
