<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RadarColdOutreachMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $companyName,
        public string $trackingUrl,
        public ?string $firstName = null,
    ) {}

    public function envelope(): Envelope
    {
        $fromAddress = (string) config('mail.from.address');
        $sender = new Address($fromAddress, 'Frederick López');

        $subjectTarget = $this->companyName !== '' && $this->companyName !== 'su empresa'
            ? $this->companyName
            : 'su empresa';

        return new Envelope(
            from: $sender,
            replyTo: [$sender],
            subject: "{$subjectTarget} — una pregunta sobre el DGCP",
        );
    }

    public function content(): Content
    {
        $greeting = $this->firstName !== null && trim($this->firstName) !== ''
            ? trim($this->firstName)
            : $this->companyName;

        return new Content(
            markdown: 'emails.radar-cold-outreach',
            with: [
                'companyName' => $this->companyName,
                'trackingUrl' => $this->trackingUrl,
                'greeting' => $greeting,
            ],
        );
    }
}
