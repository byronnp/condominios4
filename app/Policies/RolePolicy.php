<?php

namespace App\Policies;

use App\Models\Condominium;
use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user, mixed $subject = null, ?Condominium $condominium = null): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $this->canAccessCondominium($user, $condominium, 'roles.view');
    }

    public function view(User $user, Role $role, ?Condominium $condominium = null): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $this->canAccessRole($user, $role, $condominium, 'roles.view');
    }

    public function create(User $user, mixed $subject = null, ?Condominium $condominium = null): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $this->canAccessCondominium($user, $condominium, 'roles.manage');
    }

    public function update(User $user, Role $role, ?Condominium $condominium = null): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return ! $role->is_system && $this->canAccessRole($user, $role, $condominium, 'roles.manage');
    }

    public function delete(User $user, Role $role, ?Condominium $condominium = null): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return ! $role->is_system && $this->canAccessRole($user, $role, $condominium, 'roles.manage');
    }

    public function managePermissions(User $user, Role $role, ?Condominium $condominium = null): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return ! $role->is_system && $this->canAccessRole($user, $role, $condominium, 'roles.manage');
    }

    private function canAccessRole(User $user, Role $role, ?Condominium $condominium, string $permission): bool
    {
        if ($condominium === null || $role->condominium_id !== $condominium->id) {
            return false;
        }

        return $this->canAccessCondominium($user, $condominium, $permission);
    }

    private function canAccessCondominium(User $user, ?Condominium $condominium, string $permission): bool
    {
        if ($condominium === null || ! $condominium->is_active) {
            return false;
        }

        return in_array((int) $condominium->id, $user->manageableCondominiumIds($permission), true);
    }
}
