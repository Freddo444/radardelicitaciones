<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BankTransferConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu pago fue confirmado — tu cuenta está activa',
        );
    }

    public function content(): Content
    {
        $subscription = $this->payment->subscription;

        return new Content(
            markdown: 'emails.bank-transfer-confirmed',
            with: [
                'name' => $subscription?->owner?->name,
                'amount' => $this->payment->amount,
                'periodEnd' => $subscription?->current_period_end,
                'url' => route('dashboard'),
            ],
        );
    }
}
