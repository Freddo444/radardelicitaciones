<?php

namespace App\Observers;

use App\Jobs\SendPaymentInvoiceJob;
use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        if ($this->shouldQueueInvoice($payment)) {
            SendPaymentInvoiceJob::dispatch($payment->id);
        }
    }

    public function updated(Payment $payment): void
    {
        if (! $payment->wasChanged('status')) {
            return;
        }

        if ($payment->getOriginal('status') === 'completed') {
            return;
        }

        if ($this->shouldQueueInvoice($payment)) {
            SendPaymentInvoiceJob::dispatch($payment->id);
        }
    }

    private function shouldQueueInvoice(Payment $payment): bool
    {
        if ($payment->status !== 'completed' || $payment->paid_at === null) {
            return false;
        }

        return $payment->invoice_emailed_at === null;
    }
}
