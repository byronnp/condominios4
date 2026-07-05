<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    public function update(User $user, Unit $unit): bool
    {
        return $user->isPlatformAdmin()
            || $user->hasPermission('units.manage', $unit->condominium);
    }
}
