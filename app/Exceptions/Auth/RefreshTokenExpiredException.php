<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class RefreshTokenExpiredException extends RuntimeException
{
    public function __construct(string $message = 'Refresh token expirado.')
    {
        parent::__construct($message);
    }
}
