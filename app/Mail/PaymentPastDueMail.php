<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentPastDueMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscription $subscription) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'No pudimos procesar tu pago — actualiza tu método',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payment-past-due',
            with: [
                'name' => $this->subscription->owner?->name,
                'url' => route('billing.index'),
                'graceEndsAt' => $this->subscription->grace_ends_at?->translatedFormat('d \d\e F'),
            ],
        );
    }
}
