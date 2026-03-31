<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with('subscription.owner');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($gateway = $request->input('gateway')) {
            $query->where('gateway', $gateway);
        }

        $payments = $query->latest('created_at')->paginate(25)->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    public function confirm(Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'Este pago no esta pendiente.');
        }

        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        // Activate subscription if needed
        $subscription = $payment->subscription;
        if ($subscription && $subscription->isPending()) {
            SubscriptionService::activate($subscription);
        } elseif ($subscription && $subscription->isActive()) {
            $subscription->update(['current_period_end' => now()->addMonth()]);
        }

        return back()->with('success', "Pago #{$payment->id} confirmado. Suscripcion actualizada.");
    }
}
