<?php

namespace App\Policies;

use App\Models\User;

class PlatformAdministratorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function view(User $user, User $administrator): bool
    {
        return $user->isPlatformAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function update(User $user, User $administrator): bool
    {
        return $user->isPlatformAdmin();
    }

    public function updateStatus(User $user, User $administrator): bool
    {
        return $user->isPlatformAdmin();
    }

    public function delete(User $user, User $administrator): bool
    {
        return $user->isPlatformAdmin();
    }
}
