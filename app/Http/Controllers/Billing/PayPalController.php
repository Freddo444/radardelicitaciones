<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalController extends Controller
{
    /**
     * User returns from PayPal after approval (existing subscriber paying).
     */
    public function return(Request $request)
    {
        return redirect()->route('billing.index')
            ->with('success', 'Pago procesado correctamente.');
    }

    /**
     * User cancelled PayPal payment.
     */
    public function cancel()
    {
        return redirect()->route('billing.index')
            ->with('warning', 'Pago cancelado.');
    }

    /**
     * PayPal webhook handler — handles subscription lifecycle + payments.
     */
    public function webhook(Request $request)
    {
        if (! $this->verifyWebhook($request)) {
            Log::warning('[PayPal] Webhook signature verification failed');

            return response('Invalid signature', 401);
        }

        $event = $request->input('event_type');
        $resource = $request->input('resource', []);

        Log::info('[PayPal] Webhook received', ['event' => $event]);

        match ($event) {
            'PAYMENT.SALE.COMPLETED' => $this->handleSaleCompleted($resource),
            'BILLING.SUBSCRIPTION.ACTIVATED' => $this->handleSubscriptionActivated($resource),
            'BILLING.SUBSCRIPTION.CANCELLED' => $this->handleSubscriptionCancelled($resource),
            'BILLING.SUBSCRIPTION.SUSPENDED' => $this->handleSubscriptionSuspended($resource),
            'BILLING.SUBSCRIPTION.PAYMENT.FAILED' => $this->handlePaymentFailed($resource),
            default => Log::info('[PayPal] Unhandled event', ['event' => $event]),
        };

        return response('OK', 200);
    }

    /**
     * Recurring payment completed — record it and extend period.
     */
    private function handleSaleCompleted(array $resource): void
    {
        $paypalSubId = $resource['billing_agreement_id'] ?? null;
        if (! $paypalSubId) {
            return;
        }

        $subscription = Subscription::where('gateway_subscription_id', $paypalSubId)->first();
        if (! $subscription) {
            Log::warning('[PayPal] Sale completed for unknown subscription', ['id' => $paypalSubId]);

            return;
        }

        $amount = (float) ($resource['amount']['total'] ?? $resource['amount']['value'] ?? 0);
        $currency = $resource['amount']['currency'] ?? $resource['amount']['currency_code'] ?? 'USD';
        $saleId = $resource['id'] ?? null;

        // Avoid duplicate payments
        if ($saleId && Payment::where('gateway_payment_id', $saleId)->exists()) {
            return;
        }

        Payment::create([
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'currency' => $currency,
            'gateway' => 'paypal',
            'gateway_payment_id' => $saleId,
            'status' => 'completed',
            'paid_at' => now(),
            'notes' => 'Webhook: PAYMENT.SALE.COMPLETED',
        ]);

        $subscription->update([
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        Log::info('[PayPal] Recurring payment recorded', ['subscription' => $subscription->id, 'amount' => $amount]);
    }

    private function handleSubscriptionActivated(array $resource): void
    {
        $paypalSubId = $resource['id'] ?? null;
        $subscription = Subscription::where('gateway_subscription_id', $paypalSubId)->first();

        if ($subscription && ! $subscription->isActive()) {
            $subscription->update(['status' => 'active']);
            Log::info('[PayPal] Subscription activated', ['subscription' => $subscription->id]);
        }
    }

    private function handleSubscriptionCancelled(array $resource): void
    {
        $paypalSubId = $resource['id'] ?? null;
        $subscription = Subscription::where('gateway_subscription_id', $paypalSubId)->first();

        if ($subscription) {
            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
            Log::info('[PayPal] Subscription cancelled', ['subscription' => $subscription->id]);
        }
    }

    private function handleSubscriptionSuspended(array $resource): void
    {
        $paypalSubId = $resource['id'] ?? null;
        $subscription = Subscription::where('gateway_subscription_id', $paypalSubId)->first();

        if ($subscription) {
            $subscription->update(['status' => 'suspended']);
            Log::info('[PayPal] Subscription suspended', ['subscription' => $subscription->id]);
        }
    }

    private function handlePaymentFailed(array $resource): void
    {
        $paypalSubId = $resource['id'] ?? null;
        $subscription = Subscription::where('gateway_subscription_id', $paypalSubId)->first();

        if ($subscription) {
            $subscription->update(['status' => 'past_due']);
            Log::info('[PayPal] Payment failed', ['subscription' => $subscription->id]);
        }
    }

    private function verifyWebhook(Request $request): bool
    {
        $webhookId = config('services.paypal.webhook_id');
        if (! $webhookId) {
            return false;
        }

        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            return false;
        }

        $response = Http::withToken($accessToken)
            ->post($this->apiUrl('/v1/notifications/verify-webhook-signature'), [
                'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
                'cert_url' => $request->header('PAYPAL-CERT-URL'),
                'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
                'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
                'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
                'webhook_id' => $webhookId,
                'webhook_event' => $request->all(),
            ]);

        return $response->json('verification_status') === 'SUCCESS';
    }

    private function getAccessToken(): ?string
    {
        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.secret');

        if (! $clientId || ! $secret) {
            return null;
        }

        $response = Http::asForm()
            ->withBasicAuth($clientId, $secret)
            ->post($this->apiUrl('/v1/oauth2/token'), [
                'grant_type' => 'client_credentials',
            ]);

        return $response->json('access_token');
    }

    private function apiUrl(string $path): string
    {
        $base = config('services.paypal.sandbox')
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';

        return $base.$path;
    }
}
