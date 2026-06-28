<?php

namespace App\Policies;

use App\Models\Condominium;
use App\Models\User;

class CondominiumPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function view(User $user, Condominium $condominium): bool
    {
        return $user->isPlatformAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function update(User $user, Condominium $condominium): bool
    {
        return $user->isPlatformAdmin();
    }

    public function updateStatus(User $user, Condominium $condominium): bool
    {
        return $user->isPlatformAdmin();
    }

    public function delete(User $user, Condominium $condominium): bool
    {
        return $user->isPlatformAdmin();
    }
}
