<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RadarColdOutreachMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $companyName,
        public string $trackingUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'En compras públicas, enterarse tarde cuesta caro',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.radar-cold-outreach',
            with: [
                'companyName' => $this->companyName,
                'trackingUrl' => $this->trackingUrl,
            ],
        );
    }
}
