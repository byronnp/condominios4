<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class InvalidCredentialsException extends RuntimeException
{
    public function __construct(string $message = 'Credenciales inválidas.')
    {
        parent::__construct($message);
    }
}
