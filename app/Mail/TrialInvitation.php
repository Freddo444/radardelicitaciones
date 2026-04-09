<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $password,
        public int $trialDays,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu prueba gratuita de Radar de Licitaciones',
        );
    }

    public function content(): Content
    {
        $this->user->loadMissing('subscription');

        return new Content(
            markdown: 'mail.trial-invitation',
            with: [
                'loginUrl' => route('login'),
                'trialDays' => $this->trialDays,
                'trialEndsAt' => $this->user->subscription?->trial_ends_at,
                'trialParseLimit' => $this->user->subscription?->trial_parse_limit,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
