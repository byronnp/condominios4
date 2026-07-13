<?php

namespace App\Mail;

use App\Models\UserAccessInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserAccessInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly UserAccessInvitation $invitation,
        public readonly string $plainToken,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->invitation->condominium_id === null
            ? 'Activa tu acceso de administrador de plataforma'
            : 'Activa tu acceso al condominio';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.user-access-invitation', with: [
            'invitation' => $this->invitation,
            'activationUrl' => rtrim(config('invitations.frontend_url'), '/').'/#/activar-acceso?token='.urlencode($this->plainToken),
            'expiresHours' => config('invitations.expires_hours'),
        ]);
    }
}
