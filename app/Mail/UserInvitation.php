<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $password,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitación a SECP Monitor',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.user-invitation',
            with: [
                'url' => config('app.url'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
