<?php

namespace App\Mail;

use App\Models\Bid;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BidChangeNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Bid $bid,
        public array $changes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[SECP] Cambio detectado: {$this->bid->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bid-change-notification',
            with: [
                'bid' => $this->bid,
                'changes' => $this->changes,
            ],
        );
    }
}
