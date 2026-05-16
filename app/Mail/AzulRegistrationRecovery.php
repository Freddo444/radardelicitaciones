<?php

namespace App\Mail;

use App\Models\PendingRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class AzulRegistrationRecovery extends Mailable
{
    use Queueable, SerializesModels;

    public string $recoveryUrl;

    public function __construct(
        public PendingRegistration $pending,
    ) {
        $this->recoveryUrl = URL::temporarySignedRoute(
            'register.recover',
            now()->addHours(48),
            ['pending' => $pending->id]
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->pending->intended_email,
            subject: 'Tu pago fue confirmado — completa tu cuenta en Radar de Licitaciones',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.azul-registration-recovery',
            with: [
                'recoveryUrl' => $this->recoveryUrl,
                'pending' => $this->pending,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
