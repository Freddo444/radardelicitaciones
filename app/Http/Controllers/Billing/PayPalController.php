<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Mail\PaymentPastDueMail;
use App\Mail\SubscriptionEndedMail;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PayPalController extends Controller
{
    /**
     * User returns from PayPal after approval (existing subscriber paying).
     */
    public function return(Request $request)
    {
        return redirect()->route('billing.index')
            ->with(array_filter([
                'success' => 'Pago procesado correctamente.',
                '_umami' => umami_flash_payload('paypal_return_success', ['flow' => 'billing']),
            ], fn ($v) => $v !== null));
    }

    /**
     * User cancelled PayPal payment.
     */
    public function cancel()
    {
        return redirect()->route('billing.index')
            ->with(array_filter([
                'warning' => 'Pago cancelado.',
                '_umami' => umami_flash_payload('paypal_checkout_cancelled', ['flow' => 'billing']),
            ], fn ($v) => $v !== null));
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
            'CUSTOMER.DISPUTE.CREATED', 'CUSTOMER.DISPUTE.UPDATED' => $this->handleDisputeCreated($resource),
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

        $period = $subscription->billing_cycle === 'annual' ? now()->addYear() : now()->addMonth();

        // A successful charge clears any dunning state so the next failure
        // starts a fresh grace window and re-notifies the customer.
        $subscription->update([
            'status' => 'active',
            'current_period_end' => $period,
            'grace_ends_at' => null,
            'payment_failed_notified_at' => null,
        ]);

        Log::info('[PayPal] Recurring payment recorded', ['subscription' => $subscription->id, 'amount' => $amount]);
    }

    private function handleSubscriptionActivated(array $resource): void
    {
        $paypalSubId = $resource['id'] ?? null;
        $subscription = Subscription::where('gateway_subscription_id', $paypalSubId)->first();

        if ($subscription && ! $subscription->isActive()) {
            $subscription->update([
                'status' => 'active',
                'grace_ends_at' => null,
                'payment_failed_notified_at' => null,
            ]);
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
            $this->mailOwner($subscription, new SubscriptionEndedMail($subscription, 'cancelled'));
            Log::info('[PayPal] Subscription cancelled', ['subscription' => $subscription->id]);
        }
    }

    private function handleSubscriptionSuspended(array $resource): void
    {
        $paypalSubId = $resource['id'] ?? null;
        $subscription = Subscription::where('gateway_subscription_id', $paypalSubId)->first();

        if ($subscription) {
            $subscription->update(['status' => 'suspended']);
            $this->mailOwner($subscription, new SubscriptionEndedMail($subscription, 'suspended'));
            Log::info('[PayPal] Subscription suspended', ['subscription' => $subscription->id]);
        }
    }

    /**
     * A recurring charge failed. PayPal keeps retrying, so don't lock the
     * customer out immediately: open a grace window (access preserved via
     * Subscription::isActive) and send one dunning email per failure episode.
     */
    private function handlePaymentFailed(array $resource): void
    {
        $paypalSubId = $resource['id'] ?? null;
        $subscription = Subscription::where('gateway_subscription_id', $paypalSubId)->first();

        if (! $subscription) {
            return;
        }

        // A new episode = not already past_due, or a prior grace window that
        // was cleared on recovery. Retries within an episode don't re-arm it.
        $newEpisode = $subscription->status !== 'past_due' || ! $subscription->grace_ends_at;

        $subscription->update([
            'status' => 'past_due',
            'grace_ends_at' => $newEpisode
                ? now()->addDays(Subscription::DUNNING_GRACE_DAYS)
                : $subscription->grace_ends_at,
        ]);

        if ($newEpisode && ! $subscription->payment_failed_notified_at) {
            $this->mailOwner($subscription, new PaymentPastDueMail($subscription));
            $subscription->update(['payment_failed_notified_at' => now()]);
        }

        Log::info('[PayPal] Payment failed', [
            'subscription' => $subscription->id,
            'new_episode' => $newEpisode,
            'grace_ends_at' => $subscription->grace_ends_at?->toDateString(),
        ]);
    }

    /**
     * A customer opened a dispute/chargeback. Don't auto-suspend (disputes can
     * be frivolous) — alert loudly so a human reviews it.
     */
    private function handleDisputeCreated(array $resource): void
    {
        $disputeId = $resource['dispute_id'] ?? $resource['id'] ?? null;
        $reason = $resource['reason'] ?? 'unknown';
        $status = $resource['status'] ?? 'unknown';

        Log::warning('[PayPal] Dispute received', [
            'dispute_id' => $disputeId,
            'reason' => $reason,
            'status' => $status,
        ]);

        if (function_exists('\Sentry\captureMessage')) {
            \Sentry\captureMessage("PayPal dispute {$disputeId} ({$reason}, {$status}) — review required");
        }

        $inbox = config('services.support.inbox') ?? config('services.support.email');
        if ($inbox) {
            try {
                Mail::raw(
                    "Se recibió una disputa/contracargo de PayPal.\n\n".
                    "ID: {$disputeId}\nMotivo: {$reason}\nEstado: {$status}\n\n".
                    'Revisa el panel de PayPal para responder dentro del plazo.',
                    fn ($m) => $m->to($inbox)->subject("[Radar] Disputa PayPal {$disputeId}")
                );
            } catch (\Throwable $e) {
                Log::error('[PayPal] Dispute admin alert failed', ['error' => $e->getMessage()]);
            }
        }
    }

    /** Send a mailable to the subscription owner without letting a mail failure break webhook handling. */
    private function mailOwner(Subscription $subscription, Mailable $mail): void
    {
        $owner = $subscription->owner;
        if (! $owner || ! filter_var($owner->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($owner->email)->queue($mail);
        } catch (\Throwable $e) {
            Log::error('[PayPal] Owner notification failed', [
                'subscription' => $subscription->id,
                'mailable' => $mail::class,
                'error' => $e->getMessage(),
            ]);
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
