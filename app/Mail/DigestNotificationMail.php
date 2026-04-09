<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DigestNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Collection $bids) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Resumen — {$this->bids->count()} nueva(s) convocatoria(s)",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.digest-notification',
            with: [
                'bids' => $this->bids,
                'convocatoriasUrl' => route('convocatorias.index'),
            ],
        );
    }
}
