<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class UserAccessDisabledException extends RuntimeException
{
    public function __construct(string $message = 'Tu acceso aún no ha sido activado. Revisa tu correo de invitación.')
    {
        parent::__construct($message);
    }
}
