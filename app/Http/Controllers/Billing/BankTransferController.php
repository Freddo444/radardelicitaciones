<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankTransferController extends Controller
{
    public function show()
    {
        $subscription = Auth::user()->subscription;

        return view('billing.bank-transfer', compact('subscription'));
    }

    public function uploadReceipt(Request $request)
    {
        $request->validate([
            'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'notes' => 'nullable|string|max:500',
        ]);

        $subscription = Auth::user()->subscription;
        if (! $subscription) {
            return back()->with('error', 'No se encontro suscripcion.');
        }

        $path = $request->file('receipt')->store("receipts/{$subscription->id}", 'local');

        Payment::create([
            'subscription_id' => $subscription->id,
            'amount' => $subscription->monthly_amount,
            'currency' => 'USD',
            'gateway' => 'bank_transfer',
            'status' => 'pending',
            'notes' => "Comprobante: {$path}".($request->notes ? " — {$request->notes}" : ''),
        ]);

        return redirect()->route('billing.index')
            ->with('success', 'Comprobante enviado. Tu pago sera verificado por un administrador.');
    }
}
