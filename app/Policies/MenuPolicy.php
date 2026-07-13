<?php

namespace App\Policies;

use App\Models\Menu;
use App\Models\User;

class MenuPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function update(User $user, Menu $menu): bool
    {
        return $user->isPlatformAdmin();
    }

    public function delete(User $user, Menu $menu): bool
    {
        return $user->isPlatformAdmin();
    }
}
