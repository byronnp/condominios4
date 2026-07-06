<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class InvitationExpiredException extends RuntimeException
{
    public function __construct(string $message = 'La invitación ha expirado.')
    {
        parent::__construct($message);
    }
}
