<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invitation $invitation,
    ) {
        $this->invitation->load('company', 'inviter');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invitación a {$this->invitation->company->razon_social} — Radar de Licitaciones",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invitation',
            with: [
                'acceptUrl' => route('invitation.show', $this->invitation->token),
                'companyName' => $this->invitation->company->razon_social,
                'inviterName' => $this->invitation->inviter->name,
                'expiresAt' => $this->invitation->expires_at,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
