<?php

namespace App\Domain\Users\Services;

use App\Models\Condominium;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function updateStatus(User $actor, User $user, bool $isActive, ?int $contextCondominiumId = null): void
    {
        if ($actor->isPlatformAdmin()) {
            $user->update(['is_access_enabled' => $isActive]);

            return;
        }

        $manageableIds = $actor->manageableCondominiumIds('users.status');
        $targetIds = DB::table('condominium_user')->where('user_id', $user->id)
            ->whereNull('deleted_at')->pluck('condominium_id')->map(fn ($id): int => (int) $id)->all();
        $allowedIds = array_values(array_intersect($manageableIds, $targetIds));

        if ($contextCondominiumId !== null) {
            abort_unless(in_array($contextCondominiumId, $allowedIds, true), 403);
            $allowedIds = [$contextCondominiumId];
        }

        abort_if($allowedIds === [], 403);
        abort_if(count($allowedIds) > 1, 422, 'Debe indicar X-Condominium-Id para cambiar el estado local.');

        DB::table('condominium_user')->where('user_id', $user->id)
            ->where('condominium_id', $allowedIds[0])->whereNull('deleted_at')
            ->update(['is_active' => $isActive, 'updated_at' => now()]);
    }

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): User
    {
        return DB::transaction(function () use ($actor, $data): User {
            $assignments = Arr::pull($data, 'assignments', []);
            $user = User::create([...$data, 'password' => null, 'is_access_enabled' => $data['is_access_enabled'] ?? true]);
            $this->syncAssignments($user, $assignments, $actor->isPlatformAdmin());

            return $user->fresh('documentType');
        });
    }

    /** @param array<string, mixed> $data */
    public function createInCondominium(User $actor, Condominium $condominium, array $data): User
    {
        return $this->create($actor, $this->withCondominiumAssignments($condominium, $data));
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, User $user, array $data): User
    {
        return DB::transaction(function () use ($actor, $user, $data): User {
            $hasAssignments = array_key_exists('assignments', $data);
            $assignments = Arr::pull($data, 'assignments', []);
            $user->update($data);
            if ($hasAssignments) {
                if (! $actor->isPlatformAdmin()) {
                    $assignments = $this->mergeExistingAssignments($user, $assignments);
                }
                $this->syncAssignments($user, $assignments, $actor->isPlatformAdmin());
            }

            return $user->fresh('documentType');
        });
    }

    /** @param array<string, mixed> $data */
    public function updateInCondominium(User $actor, User $user, Condominium $condominium, array $data): User
    {
        return $this->update($actor, $user, $this->withCondominiumAssignments($condominium, $data));
    }

    public function updateStatusInCondominium(User $actor, User $user, Condominium $condominium, bool $isActive): void
    {
        $updated = DB::table('condominium_user')
            ->where('user_id', $user->id)
            ->where('condominium_id', $condominium->id)
            ->whereNull('deleted_at')
            ->update(['is_active' => $isActive, 'updated_at' => now()]);

        abort_if($updated === 0, 404);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withCondominiumAssignments(Condominium $condominium, array $data): array
    {
        if (array_key_exists('role_id', $data)) {
            $data['assignments'] = [
                ['condominium_id' => $condominium->id, 'role_id' => $data['role_id']],
            ];
            unset($data['role_id']);

            return $data;
        }

        if (array_key_exists('assignments', $data)) {
            $data['assignments'] = collect($data['assignments'])
                ->map(fn (array $assignment): array => [
                    ...$assignment,
                    'condominium_id' => $condominium->id,
                ])
                ->all();
        }

        return $data;
    }

    /**
     * @param  array<int, array{condominium_id: int|null, role_id: int}>  $changes
     * @return array<int, array{condominium_id: int|null, role_id: int}>
     */
    private function mergeExistingAssignments(User $user, array $changes): array
    {
        $existing = DB::table('condominium_user')
            ->join('condominium_user_role', 'condominium_user_role.condominium_user_id', '=', 'condominium_user.id')
            ->where('condominium_user.user_id', $user->id)->whereNull('condominium_user.deleted_at')
            ->whereNull('condominium_user_role.deleted_at')
            ->get(['condominium_user.condominium_id', 'condominium_user_role.role_id'])
            ->map(fn ($row): array => ['condominium_id' => (int) $row->condominium_id, 'role_id' => (int) $row->role_id])
            ->keyBy('condominium_id');

        foreach ($changes as $change) {
            $existing->put((int) $change['condominium_id'], $change);
        }

        return $existing->values()->all();
    }

    /** @param array<int, array{condominium_id: int|null, role_id: int}> $assignments */
    private function syncAssignments(User $user, array $assignments, bool $syncGlobalRoles): void
    {
        $condominiumAssignments = collect($assignments)->filter(fn (array $item): bool => $item['condominium_id'] !== null);
        $globalAssignments = collect($assignments)->filter(fn (array $item): bool => $item['condominium_id'] === null);
        $this->validateCondominiumAssignments($condominiumAssignments->values()->all());

        $currentMemberships = DB::table('condominium_user')->where('user_id', $user->id)->whereNull('deleted_at')->get();
        $requestedCondominiumIds = $condominiumAssignments->pluck('condominium_id')->map(fn ($id): int => (int) $id)->all();

        foreach ($currentMemberships as $membership) {
            if (! in_array((int) $membership->condominium_id, $requestedCondominiumIds, true)) {
                DB::table('condominium_user_role')->where('condominium_user_id', $membership->id)->whereNull('deleted_at')
                    ->update(['deleted_at' => now(), 'updated_at' => now()]);
                DB::table('condominium_user')->where('id', $membership->id)
                    ->update(['is_active' => false, 'deleted_at' => now(), 'updated_at' => now()]);
            }
        }

        foreach ($condominiumAssignments as $assignment) {
            DB::table('condominium_user')->updateOrInsert([
                'user_id' => $user->id,
                'condominium_id' => $assignment['condominium_id'],
            ], [
                'is_active' => true, 'joined_at' => now(), 'deleted_at' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $membershipId = DB::table('condominium_user')->where('user_id', $user->id)
                ->where('condominium_id', $assignment['condominium_id'])->value('id');
            DB::table('condominium_user_role')->where('condominium_user_id', $membershipId)
                ->where('role_id', '!=', $assignment['role_id'])->whereNull('deleted_at')
                ->update(['deleted_at' => now(), 'updated_at' => now()]);
            DB::table('condominium_user_role')->updateOrInsert([
                'condominium_user_id' => $membershipId, 'role_id' => $assignment['role_id'],
            ], ['deleted_at' => null, 'created_at' => now(), 'updated_at' => now()]);
        }

        $globalRoleIds = $globalAssignments->pluck('role_id')->all();
        if ($syncGlobalRoles) {
            DB::table('role_user')->where('user_id', $user->id)
                ->whereIn('role_id', Role::query()->whereNull('condominium_id')->pluck('id'))
                ->when($globalRoleIds !== [], fn ($query) => $query->whereNotIn('role_id', $globalRoleIds))
                ->delete();
        }

        if ($syncGlobalRoles && $globalRoleIds !== []) {
            $tenantId = DB::table('tenants')->where('slug', 'admin-platform')->value('id');
            foreach ($globalRoleIds as $roleId) {
                DB::table('role_user')->updateOrInsert(
                    ['user_id' => $user->id, 'role_id' => $roleId, 'tenant_id' => $tenantId],
                    ['created_at' => now(), 'updated_at' => now()],
                );
            }
        }
    }

    /**
     * @param  array<int, array{condominium_id: int|null, role_id: int}>  $assignments
     */
    private function validateCondominiumAssignments(array $assignments): void
    {
        $errors = [];
        $roleIds = collect($assignments)
            ->filter(fn (array $assignment): bool => $assignment['condominium_id'] !== null)
            ->pluck('role_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->all();

        if ($roleIds === []) {
            return;
        }

        $roles = Role::withTrashed()
            ->whereIn('id', $roleIds)
            ->get()
            ->keyBy('id');

        foreach ($assignments as $index => $assignment) {
            $condominiumId = $assignment['condominium_id'];

            if ($condominiumId === null) {
                continue;
            }

            $role = $roles->get((int) $assignment['role_id']);

            if (! $role) {
                $errors["assignments.$index.role_id"][] = 'El rol seleccionado no existe.';

                continue;
            }

            if ($role->condominium === null || ! $role->condominium->is_active) {
                $errors["assignments.$index.role_id"][] = 'El condominio del rol está inactivo.';
            }

            if ($role->deleted_at !== null) {
                $errors["assignments.$index.role_id"][] = 'El rol seleccionado fue eliminado.';
            }

            if (! $role->is_active) {
                $errors["assignments.$index.role_id"][] = 'El rol seleccionado está inactivo.';
            }

            if ((int) $role->condominium_id !== (int) $condominiumId) {
                $errors["assignments.$index.role_id"][] = 'El rol no pertenece al condominio indicado.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
