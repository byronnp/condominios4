<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->isPlatformAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->isPlatformAdmin();
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->isPlatformAdmin();
    }
}
