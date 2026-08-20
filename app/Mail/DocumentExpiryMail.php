<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\VaultDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DocumentExpiryMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param Collection<int, VaultDocument> $documents */
    public function __construct(
        public Company $company,
        public Collection $documents,
    ) {}

    public function envelope(): Envelope
    {
        $count = $this->documents->count();

        return new Envelope(
            subject: $count === 1
                ? 'Un documento de tu empresa está por vencer'
                : "{$count} documentos de tu empresa están por vencer",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.document-expiry',
            with: [
                'companyName' => $this->company->razon_social,
                'url' => route('documentos.index'),
            ],
        );
    }
}
