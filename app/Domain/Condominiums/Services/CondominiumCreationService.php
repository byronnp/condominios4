<?php

namespace App\Domain\Condominiums\Services;

use App\Models\Condominium;
use App\Models\CondominiumBillingSetting;
use App\Models\CondominiumFeature;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Mail\CondominiumAdministratorCreatedMail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        $administratorToNotify = null;

        try {
            if ($logo !== null) {
                $logoPath = $this->storeLogo($logo);
                $condominiumData['logo_path'] = $logoPath;
            }

            $condominium = DB::transaction(function () use ($condominiumData, $featureIds, $currency, $administratorData, &$administratorToNotify): Condominium {
                $condominium = Condominium::create($condominiumData);

                $this->syncFeatures($condominium, $featureIds);
                $this->createBillingSetting($condominium, $currency);

                if ($administratorData !== null) {
                    $administratorToNotify = $this->attachAdministrator($condominium, $administratorData);
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

            if ($administratorToNotify !== null) {
                Mail::to($administratorToNotify->email)->queue(
                    new CondominiumAdministratorCreatedMail($administratorToNotify, $condominium)
                );
            }

            return $condominium;
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

    /**
     * @param  array<string, mixed>  $condominiumData
     * @param  array<int, int>|null  $featureIds
     * @param  array<string, mixed>|null  $administratorData
     */
    public function update(
        Condominium $condominium,
        array $condominiumData,
        ?array $featureIds = null,
        ?string $currency = null,
        ?array $administratorData = null,
        ?UploadedFile $logo = null,
    ): Condominium {
        $logoPath = null;
        $previousLogoPath = $condominium->logo_path;
        $administratorToNotify = null;

        try {
            if ($logo !== null) {
                $logoPath = $this->storeLogo($logo);
                $condominiumData['logo_path'] = $logoPath;
            }

            $condominium = DB::transaction(function () use ($condominium, $condominiumData, $featureIds, $currency, $administratorData, &$administratorToNotify): Condominium {
                $condominium->fill($condominiumData)->save();

                if ($featureIds !== null) {
                    $this->replaceFeatures($condominium, $featureIds);
                }

                if ($currency !== null) {
                    $this->updateBillingSetting($condominium, $currency);
                }

                if ($administratorData !== null) {
                    $administratorToNotify = $this->attachAdministrator($condominium, $administratorData);
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

            if ($administratorToNotify !== null) {
                Mail::to($administratorToNotify->email)->queue(
                    new CondominiumAdministratorCreatedMail($administratorToNotify, $condominium)
                );
            }

            if ($logoPath !== null && $previousLogoPath !== null && $previousLogoPath !== $logoPath) {
                try {
                    Storage::disk($this->logoDisk())->delete($previousLogoPath);
                } catch (Throwable $cleanupException) {
                    Log::warning('No se pudo limpiar el logo anterior del condominio luego de actualizar.', [
                        'logo_path' => $previousLogoPath,
                        'exception' => $cleanupException->getMessage(),
                    ]);
                }
            }

            return $condominium;
        } catch (Throwable $exception) {
            if ($logoPath !== null) {
                try {
                    Storage::disk($this->logoDisk())->delete($logoPath);
                } catch (Throwable $cleanupException) {
                    Log::warning('No se pudo limpiar el logo del condominio luego de fallar la actualización.', [
                        'logo_path' => $logoPath,
                        'exception' => $cleanupException->getMessage(),
                    ]);
                }
            }

            throw $exception;
        }
    }

    public function delete(Condominium $condominium): void
    {
        $logoPath = $condominium->logo_path;

        DB::transaction(function () use ($condominium): void {
            $condominium->delete();
        });

        if ($logoPath === null) {
            return;
        }

        try {
            Storage::disk($this->logoDisk())->delete($logoPath);
        } catch (Throwable $exception) {
            Log::warning('No se pudo eliminar el logo del condominio luego de borrar el registro.', [
                'logo_path' => $logoPath,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function logoDisk(): string
    {
        return config('filesystems.logo_disk', 'public');
    }

    private function storeLogo(UploadedFile $logo): string
    {
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

        return $logoPath;
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

    /**
     * @param  array<int, int>  $featureIds
     */
    private function replaceFeatures(Condominium $condominium, array $featureIds): void
    {
        $featureIds = array_values(array_unique($featureIds));

        if ($featureIds === []) {
            CondominiumFeature::query()
                ->where('condominium_id', $condominium->id)
                ->delete();

            return;
        }

        CondominiumFeature::query()
            ->where('condominium_id', $condominium->id)
            ->whereNotIn('catalog_item_id', $featureIds)
            ->delete();

        $this->syncFeatures($condominium, $featureIds);
    }

    private function createBillingSetting(Condominium $condominium, ?string $currency): void
    {
        CondominiumBillingSetting::create([
            'condominium_id' => $condominium->id,
            'currency' => $currency ?? 'USD',
            'is_active' => true,
        ]);
    }

    private function updateBillingSetting(Condominium $condominium, ?string $currency): void
    {
        if ($currency === null) {
            return;
        }

        $billingSetting = $condominium->activeBillingSetting ?? new CondominiumBillingSetting([
            'condominium_id' => $condominium->id,
            'is_active' => true,
        ]);

        $billingSetting->fill([
            'currency' => $currency,
            'is_active' => true,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $administratorData
     */
    private function attachAdministrator(Condominium $condominium, array $administratorData): ?User
    {
        [$administrator, $wasCreated] = $this->findOrCreateAdministrator($administratorData);

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

        return $wasCreated ? $administrator : null;
    }

    /**
     * @param  array<string, mixed>  $administratorData
     * @return array{0: User, 1: bool}
     */
    private function findOrCreateAdministrator(array $administratorData): array
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
            return [User::create($administratorData), true];
        }

        $user->fill([
            'first_name' => $administratorData['first_name'],
            'last_name' => $administratorData['last_name'] ?? null,
            'email' => $administratorData['email'],
            'country' => $administratorData['country'],
            'document_type_id' => $administratorData['document_type_id'],
            'document_number' => $administratorData['document_number'],
            'phone' => $administratorData['phone'] ?? $user->phone,
            'is_access_enabled' => $administratorData['is_access_enabled'] ?? $user->is_access_enabled,
        ])->save();

        return [$user, false];
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
