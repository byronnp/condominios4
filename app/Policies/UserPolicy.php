<?php

namespace App\Policies;

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

    public function update(User $actor, User $user): bool
    {
        return $this->canManageTarget($actor, $user, 'users.update');
    }

    public function updateStatus(User $actor, User $user): bool
    {
        return $actor->id !== $user->id && $this->canManageTarget($actor, $user, 'users.status', false);
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
}
