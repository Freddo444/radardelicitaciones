<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Billing\UsdDopExchange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankTransferController extends Controller
{
    public function show()
    {
        $subscription = Auth::user()->subscription;
        $bank = config('services.bank');
        $rate = UsdDopExchange::rate();
        $amountDop = $subscription ? round($subscription->monthly_amount * $rate, 2) : null;

        return view('billing.bank-transfer', compact('subscription', 'bank', 'rate', 'amountDop'));
    }

    public function uploadReceipt(Request $request)
    {
        $request->validate([
            'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $subscription = Auth::user()->subscription;
        if (! $subscription) {
            return back()->with('error', 'No se encontró suscripción.');
        }

        $path = $request->file('receipt')->store("receipts/{$subscription->id}", 'local');

        Payment::create([
            'subscription_id' => $subscription->id,
            'amount' => $subscription->monthly_amount,
            'currency' => 'USD',
            'gateway' => 'bank_transfer',
            'status' => 'pending',
            'notes' => "Comprobante: {$path}",
        ]);

        return redirect()->route('billing.index')
            ->with(array_filter([
                'success' => 'Comprobante enviado. Tu pago será verificado por un administrador.',
                '_umami' => umami_flash_payload('bank_transfer_receipt_submitted'),
            ], fn ($v) => $v !== null));
    }
}
