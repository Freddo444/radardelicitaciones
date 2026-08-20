<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReEngagementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $daysAway,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Hay nuevas licitaciones esperándote en '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.re-engagement',
            with: [
                'name' => $this->user->name,
                'daysAway' => $this->daysAway,
                'url' => route('dashboard'),
            ],
        );
    }
}
