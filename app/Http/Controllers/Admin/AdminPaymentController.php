<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BankTransferConfirmedMail;
use App\Models\Payment;
use App\Models\PendingRegistration;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    public function orphans()
    {
        $orphans = PendingRegistration::whereNull('claimed_at')
            ->whereNull('refunded_at')
            ->latest()
            ->get();

        return view('admin.payments.orphans', compact('orphans'));
    }

    public function markRefunded(PendingRegistration $pendingRegistration)
    {
        $pendingRegistration->update(['refunded_at' => now()]);

        return back()->with('success', "Orden {$pendingRegistration->order_number} marcada como reembolsada.");
    }

    public function confirm(Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'Este pago no está pendiente.');
        }

        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        // Activate (or extend) the subscription for its already-specified plan.
        $subscription = $payment->subscription;
        if ($subscription && $subscription->isActive()) {
            $subscription->update(['current_period_end' => now()->addMonth()]);
        } elseif ($subscription) {
            // Covers pending, trialing, and expired-trial → active.
            SubscriptionService::activate($subscription);
        }

        // Notify the customer their account is now active.
        $recipient = $subscription?->owner?->email;
        if ($recipient) {
            try {
                Mail::to($recipient)->send(new BankTransferConfirmedMail($payment->fresh('subscription.owner')));
            } catch (\Throwable $e) {
                Log::error('[AdminPayment] confirmation email failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', "Pago #{$payment->id} confirmado. Cuenta activada y cliente notificado.");
    }

    /**
     * Stream a payment's uploaded bank-transfer voucher (private disk).
     */
    public function voucher(Payment $payment)
    {
        $path = $payment->receipt_path;

        if (! $path || ! Storage::disk('local')->exists($path)) {
            throw new NotFoundHttpException('Comprobante no encontrado.');
        }

        return Storage::disk('local')->response($path);
    }
}
