<?php

namespace App\Mail;

use App\Models\Bid;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BidNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Bid $bid) {}

    public function envelope(): Envelope
    {
        $buyer = $this->bid->buyer_name ?? 'N/D';
        return new Envelope(
            subject: "[SECP] {$this->bid->title} — {$buyer}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bid-notification',
            with: ['bid' => $this->bid],
        );
    }
}
