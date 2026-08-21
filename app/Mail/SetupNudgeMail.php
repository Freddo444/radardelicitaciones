<?php

namespace App\Mail;

use App\Mail\Concerns\RepliesToSupport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SetupNudgeMail extends Mailable
{
    use Queueable, RepliesToSupport, SerializesModels;

    public function __construct(
        public User $user,
        public int $openBids,
    ) {}

    public function envelope(): Envelope
    {
        $first = Str::of((string) $this->user->name)->before(' ')->trim();
        $subject = 'estás a 5 minutos de ver las licitaciones que te tocan';

        return new Envelope(
            subject: $first->isNotEmpty() ? "{$first}, {$subject}" : ucfirst($subject),
            from: $this->lifecycleFrom(),
            replyTo: $this->supportReplyTo(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.setup-nudge',
            with: [
                'name' => $this->user->name,
                'openBids' => $this->openBids,
                'url' => route('company-setup.show'),
            ],
        );
    }
}
