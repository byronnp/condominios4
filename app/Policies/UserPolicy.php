<?php

namespace App\Policies;

use App\Models\Condominium;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isPlatformAdmin() || $actor->manageableCondominiumIds('users.view') !== [];
    }

    public function view(User $actor, User $user): bool
    {
        return $this->canManageTarget($actor, $user, 'users.view');
    }

    public function create(User $actor): bool
    {
        return $actor->isPlatformAdmin() || $actor->manageableCondominiumIds('users.create') !== [];
    }

    public function viewAnyInCondominium(User $actor, Condominium $condominium): bool
    {
        return $actor->isPlatformAdmin() || $actor->hasPermission('users.view', $condominium);
    }

    public function createInCondominium(User $actor, Condominium $condominium): bool
    {
        return $actor->isPlatformAdmin() || $actor->hasPermission('users.create', $condominium);
    }

    public function update(User $actor, User $user): bool
    {
        return $this->canManageTarget($actor, $user, 'users.update');
    }

    public function viewInCondominium(User $actor, User $user, Condominium $condominium): bool
    {
        return $this->canManageTargetInCondominium($actor, $user, $condominium, 'users.view');
    }

    public function updateInCondominium(User $actor, User $user, Condominium $condominium): bool
    {
        return $this->canManageTargetInCondominium($actor, $user, $condominium, 'users.update');
    }

    public function updateStatus(User $actor, User $user): bool
    {
        return $actor->id !== $user->id && $this->canManageTarget($actor, $user, 'users.status', false);
    }

    public function updateStatusInCondominium(User $actor, User $user, Condominium $condominium): bool
    {
        return $actor->id !== $user->id
            && $this->canManageTargetInCondominium($actor, $user, $condominium, 'users.status', false);
    }

    private function canManageTarget(User $actor, User $target, string $permission, bool $onlyActive = true): bool
    {
        if ($actor->isPlatformAdmin()) {
            return true;
        }

        if ($target->isPlatformAdmin()) {
            return false;
        }

        $allowed = $actor->manageableCondominiumIds($permission);
        $targetCondominiums = $target->condominiums()->wherePivotNull('deleted_at');
        if ($onlyActive) {
            $targetCondominiums->wherePivot('is_active', true);
        }

        $targetIds = $targetCondominiums->pluck('condominiums.id')
            ->map(fn ($id): int => (int) $id)->all();

        return collect($targetIds)->intersect($allowed)->isNotEmpty();
    }

    private function canManageTargetInCondominium(User $actor, User $target, Condominium $condominium, string $permission, bool $onlyActive = true): bool
    {
        if ($actor->isPlatformAdmin()) {
            return $this->targetBelongsToCondominium($target, $condominium, $onlyActive);
        }

        if ($target->isPlatformAdmin() || ! $actor->hasPermission($permission, $condominium)) {
            return false;
        }

        return $this->targetBelongsToCondominium($target, $condominium, $onlyActive);
    }

    private function targetBelongsToCondominium(User $target, Condominium $condominium, bool $onlyActive = true): bool
    {
        $query = $target->condominiums()
            ->where('condominiums.id', $condominium->id)
            ->wherePivotNull('deleted_at');

        if ($onlyActive) {
            $query->wherePivot('is_active', true);
        }

        return $query->exists();
    }
}
