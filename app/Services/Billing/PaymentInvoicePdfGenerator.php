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

        $dopRate = null;
        $dopEquivalent = null;
        if ($payment->currency === 'USD') {
            $dopRate = UsdDopExchange::rate();
            $dopEquivalent = round((float) $payment->amount * $dopRate, 2);
        } elseif ($payment->currency === 'DOP') {
            $dopEquivalent = (float) $payment->amount;
        }

        // Prices are ITBIS-inclusive (18%), so break the charged total into its
        // taxable base and ITBIS portion for the receipt.
        $symbol = $payment->currency === 'DOP' ? 'RD$' : 'US$';
        $total = (float) $payment->amount;
        $itbis = round($total * 18 / 118, 2);
        $subtotal = round($total - $itbis, 2);
        $fmt = fn (float $n): string => $symbol.number_format($n, 2, '.', ',');

        return Pdf::loadView('pdf.payment-invoice', [
            'payment' => $payment,
            'merchant' => config('services.support'),
            'appName' => config('app.name'),
            'logoBase64' => $logoBase64,
            'dopRate' => $dopRate,
            'dopEquivalent' => $dopEquivalent,
            'subtotalFormatted' => $fmt($subtotal),
            'itbisFormatted' => $fmt($itbis),
        ])
            ->setPaper('letter', 'portrait')
            ->output();
    }
}
