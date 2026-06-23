<?php

namespace App\Domain\Condominiums\Services;

use App\Models\Condominium;
use App\Models\CondominiumBillingSetting;
use App\Models\CondominiumFeature;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CondominiumCreationService
{
    /**
     * @param  array<string, mixed>  $condominiumData
     * @param  array<int, int>  $featureIds
     * @param  array<string, mixed>|null  $administratorData
     */
    public function create(
        array $condominiumData,
        array $featureIds = [],
        ?string $currency = null,
        ?array $administratorData = null,
        ?UploadedFile $logo = null,
    ): Condominium {
        $logoPath = null;

        try {
            if ($logo !== null) {
                $logoPath = $logo->hashName('condominiums/logos');
                $stream = $this->logoStream($logo);

                try {
                    $stored = Storage::disk($this->logoDisk())->put($logoPath, $stream);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }

                if ($stored !== true) {
                    throw new RuntimeException('No se pudo guardar el logo del condominio.');
                }

                $condominiumData['logo_path'] = $logoPath;
            }

            return DB::transaction(function () use ($condominiumData, $featureIds, $currency, $administratorData): Condominium {
                $condominium = Condominium::create($condominiumData);

                $this->syncFeatures($condominium, $featureIds);
                $this->createBillingSetting($condominium, $currency);

                if ($administratorData !== null) {
                    $this->attachAdministrator($condominium, $administratorData);
                }

                return $condominium->fresh([
                    'type',
                    'country',
                    'province',
                    'city',
                    'features',
                    'activeBillingSetting',
                    'users.documentType',
                    'roles',
                ]);
            });
        } catch (Throwable $exception) {
            if ($logoPath !== null) {
                try {
                    Storage::disk($this->logoDisk())->delete($logoPath);
                } catch (Throwable $cleanupException) {
                    Log::warning('No se pudo limpiar el logo del condominio luego de fallar la creación.', [
                        'logo_path' => $logoPath,
                        'exception' => $cleanupException->getMessage(),
                    ]);
                }
            }

            throw $exception;
        }
    }

    private function logoDisk(): string
    {
        return config('filesystems.logo_disk', 'public');
    }

    /**
     * @return resource
     */
    private function logoStream(UploadedFile $logo): mixed
    {
        $stream = fopen($logo->getRealPath(), 'r');

        if ($stream === false) {
            throw new RuntimeException('No se pudo leer el logo del condominio.');
        }

        return $stream;
    }

    /**
     * @param  array<int, int>  $featureIds
     */
    private function syncFeatures(Condominium $condominium, array $featureIds): void
    {
        foreach (array_unique($featureIds) as $featureId) {
            $feature = CondominiumFeature::withTrashed()->firstOrNew([
                'condominium_id' => $condominium->id,
                'catalog_item_id' => $featureId,
            ]);

            if ($feature->exists && $feature->trashed()) {
                $feature->restore();

                continue;
            }

            $feature->save();
        }
    }

    private function createBillingSetting(Condominium $condominium, ?string $currency): void
    {
        CondominiumBillingSetting::create([
            'condominium_id' => $condominium->id,
            'currency' => $currency ?? 'USD',
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $administratorData
     */
    private function attachAdministrator(Condominium $condominium, array $administratorData): void
    {
        $administrator = $this->findOrCreateAdministrator($administratorData);

        DB::table('condominium_user')->updateOrInsert([
            'condominium_id' => $condominium->id,
            'user_id' => $administrator->id,
        ], [
            'is_active' => $administratorData['is_access_enabled'] ?? true,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $condominiumUserId = DB::table('condominium_user')
            ->where('condominium_id', $condominium->id)
            ->where('user_id', $administrator->id)
            ->value('id');

        $role = $this->administratorRole($condominium);

        DB::table('condominium_user_role')->updateOrInsert([
            'condominium_user_id' => $condominiumUserId,
            'role_id' => $role->id,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $administratorData
     */
    private function findOrCreateAdministrator(array $administratorData): User
    {
        $user = User::query()
            ->where('email', $administratorData['email'])
            ->orWhere(function ($query) use ($administratorData): void {
                $query->where('country', $administratorData['country'])
                    ->where('document_type_id', $administratorData['document_type_id'])
                    ->where('document_number', $administratorData['document_number']);
            })
            ->first();

        if ($user === null) {
            return User::create($administratorData);
        }

        $user->fill([
            'name' => $administratorData['name'],
            'email' => $administratorData['email'],
            'country' => $administratorData['country'],
            'document_type_id' => $administratorData['document_type_id'],
            'document_number' => $administratorData['document_number'],
            'phone' => $administratorData['phone'] ?? $user->phone,
            'is_access_enabled' => $administratorData['is_access_enabled'] ?? $user->is_access_enabled,
        ])->save();

        return $user;
    }

    private function administratorRole(Condominium $condominium): Role
    {
        $role = Role::updateOrCreate([
            'condominium_id' => $condominium->id,
            'code' => 'administrador',
        ], [
            'name' => 'Administrador',
            'description' => 'Acceso completo al condominio.',
            'is_system' => true,
            'is_active' => true,
        ]);

        $role->permissions()->sync(Permission::query()->where('is_active', true)->pluck('id')->all());

        return $role;
    }
}
