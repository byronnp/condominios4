<?php

namespace App\Exceptions\Condominiums;

use RuntimeException;

class CondominiumInactiveException extends RuntimeException
{
    public function __construct(string $message = 'El condominio indicado está inactivo.')
    {
        parent::__construct($message);
    }
}
