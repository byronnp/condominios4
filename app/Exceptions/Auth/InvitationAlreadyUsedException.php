<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class InvitationAlreadyUsedException extends RuntimeException
{
    public function __construct(string $message = 'Invitación inválida o ya utilizada.')
    {
        parent::__construct($message);
    }
}
