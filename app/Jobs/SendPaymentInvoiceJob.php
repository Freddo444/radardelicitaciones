<?php

namespace App\Jobs;

use App\Mail\PaymentInvoiceMail;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPaymentInvoiceJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $uniqueFor = 3600;

    public function __construct(
        public int $paymentId,
    ) {}

    public function uniqueId(): string
    {
        return 'payment-invoice-'.$this->paymentId;
    }

    public function handle(): void
    {
        $payment = Payment::with('subscription.owner')->find($this->paymentId);

        if (! $payment || $payment->status !== 'completed' || ! $payment->paid_at) {
            return;
        }

        if ($payment->invoice_emailed_at) {
            return;
        }

        $email = $payment->subscription?->owner?->email;
        if (! $email) {
            Log::warning('[Invoice] No recipient email for payment', ['payment_id' => $this->paymentId]);

            return;
        }

        try {
            Mail::to($email)->send(new PaymentInvoiceMail($payment));
        } catch (\Throwable $e) {
            Log::error('[Invoice] Failed to send payment invoice', [
                'payment_id' => $this->paymentId,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $payment->forceFill(['invoice_emailed_at' => now()])->saveQuietly();
    }
}
