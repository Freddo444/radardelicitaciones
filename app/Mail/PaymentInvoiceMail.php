<?php

namespace App\Mail;

use App\Models\Payment;
use App\Services\Billing\PaymentInvoicePdfGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
    ) {
        $this->payment->loadMissing(['subscription.owner']);
    }

    public function envelope(): Envelope
    {
        $support = config('services.support.email');

        return new Envelope(
            subject: 'Comprobante de pago — '.config('app.name').' (factura No. '.str_pad((string) $this->payment->id, 6, '0', STR_PAD_LEFT).')',
            replyTo: $support ? [new Address($support, config('app.name'))] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'mail.payment-invoice',
            text: 'mail.payment-invoice-plain',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $pdf = app(PaymentInvoicePdfGenerator::class)->binary($this->payment);

        return [
            Attachment::fromData(fn () => $pdf, 'Factura-RDL-'.$this->payment->id.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
