<?php

namespace App\Services\Billing;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;

class PaymentInvoicePdfGenerator
{
    public function binary(Payment $payment): string
    {
        $payment->loadMissing(['subscription.owner']);

        $logoPath = public_path('images/badgeonly.png');
        $logoBase64 = null;
        if (File::isFile($logoPath)) {
            $logoBase64 = base64_encode(File::get($logoPath));
        }

        return Pdf::loadView('pdf.payment-invoice', [
            'payment' => $payment,
            'merchant' => config('services.support'),
            'appName' => config('app.name'),
            'logoBase64' => $logoBase64,
        ])
            ->setPaper('letter', 'portrait')
            ->output();
    }
}
