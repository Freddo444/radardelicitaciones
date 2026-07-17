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
            'amount_transferred' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $subscription = Auth::user()->subscription;
        if (! $subscription) {
            return back()->with('error', 'No se encontró suscripción.');
        }

        $path = $request->file('receipt')->store("receipts/{$subscription->id}", 'local');

        $notes = "Comprobante: {$path}";
        if ($request->filled('amount_transferred')) {
            $notes .= ' — Monto declarado: RD$'.number_format((float) $request->amount_transferred, 2);
        }
        if ($request->filled('notes')) {
            $notes .= " — {$request->notes}";
        }

        Payment::create([
            'subscription_id' => $subscription->id,
            'amount' => $subscription->monthly_amount,
            'currency' => 'USD',
            'gateway' => 'bank_transfer',
            'status' => 'pending',
            'notes' => $notes,
        ]);

        return redirect()->route('billing.index')
            ->with(array_filter([
                'success' => 'Comprobante enviado. Tu pago será verificado por un administrador.',
                '_umami' => umami_flash_payload('bank_transfer_receipt_submitted'),
            ], fn ($v) => $v !== null));
    }
}
