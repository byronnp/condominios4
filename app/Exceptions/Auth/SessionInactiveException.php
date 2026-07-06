<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class SessionInactiveException extends RuntimeException
{
    public function __construct(string $message = 'Sesión inválida o expirada.')
    {
        parent::__construct($message);
    }
}
