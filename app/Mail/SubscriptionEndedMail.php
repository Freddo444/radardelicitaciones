<?php

namespace App\Mail;

use App\Mail\Concerns\RepliesToSupport;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionEndedMail extends Mailable
{
    use Queueable, RepliesToSupport, SerializesModels;

    /** @param 'suspended'|'cancelled' $reason */
    public function __construct(
        public Subscription $subscription,
        public string $reason = 'cancelled',
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->reason === 'suspended'
            ? 'Tu suscripción se suspendió — reactívala cuando quieras'
            : 'Tu suscripción fue cancelada';

        return new Envelope(
            subject: $subject,
            from: $this->lifecycleFrom(),
            replyTo: $this->supportReplyTo(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription-ended',
            with: [
                'name' => $this->subscription->owner?->name,
                'reason' => $this->reason,
                'url' => route('billing.index'),
            ],
        );
    }
}
