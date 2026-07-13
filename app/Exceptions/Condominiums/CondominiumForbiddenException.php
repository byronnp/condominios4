<?php

namespace App\Exceptions\Condominiums;

use RuntimeException;

class CondominiumForbiddenException extends RuntimeException
{
    public function __construct(string $message = 'No tienes acceso al condominio indicado.')
    {
        parent::__construct($message);
    }
}
