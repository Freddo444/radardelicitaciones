<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialEndingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $daysLeft,
    ) {}

    public function envelope(): Envelope
    {
        $when = $this->daysLeft <= 1 ? 'mañana' : 'en '.$this->daysLeft.' días';

        return new Envelope(
            subject: 'Tu prueba gratis termina '.$when,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.trial-ending',
            with: [
                'name' => $this->user->name,
                'daysLeft' => $this->daysLeft,
                'url' => route('billing.index'),
            ],
        );
    }
}
