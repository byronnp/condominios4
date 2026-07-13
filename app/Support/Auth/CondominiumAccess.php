<?php

namespace App\Support\Auth;

use App\Exceptions\Condominiums\CondominiumForbiddenException;
use App\Exceptions\Condominiums\CondominiumInactiveException;
use App\Models\Condominium;
use App\Models\User;

class CondominiumAccess
{
    public static function allows(User $user, Condominium $condominium, string $permission): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $user->hasPermission($permission, $condominium);
    }

    public static function ensure(User $user, Condominium $condominium, string $permission): void
    {
        if (! $condominium->is_active) {
            throw new CondominiumInactiveException;
        }

        if (! self::allows($user, $condominium, $permission)) {
            throw new CondominiumForbiddenException;
        }
    }
}
