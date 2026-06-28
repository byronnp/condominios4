<?php

namespace App\Mail;

use App\Models\Condominium;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CondominiumAdministratorCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $administrator,
        public readonly Condominium $condominium,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Has sido asignado como administrador de condominio',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.condominium-administrator-created',
            with: [
                'administrator' => $this->administrator,
                'condominium' => $this->condominium,
            ],
        );
    }
}
