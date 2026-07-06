<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class UserInactiveException extends RuntimeException
{
    public function __construct(string $message = 'El usuario está inactivo.')
    {
        parent::__construct($message);
    }
}
