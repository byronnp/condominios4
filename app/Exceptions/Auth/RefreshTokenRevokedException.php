<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class RefreshTokenRevokedException extends RuntimeException
{
    public function __construct(string $message = 'Refresh token revocado.')
    {
        parent::__construct($message);
    }
}
