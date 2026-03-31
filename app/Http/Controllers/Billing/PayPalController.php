<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalController extends Controller
{
    /**
     * Create a PayPal order for the subscription amount.
     */
    public function createOrder()
    {
        $user = Auth::user();
        $subscription = $user->subscription;

        if (! $subscription) {
            return response()->json(['error' => 'No subscription found'], 400);
        }

        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            return response()->json(['error' => 'PayPal authentication failed'], 500);
        }

        $response = Http::withToken($accessToken)
            ->post($this->apiUrl('/v2/checkout/orders'), [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format((float) $subscription->monthly_amount, 2, '.', ''),
                    ],
                    'description' => "Radar de Licitaciones — Plan {$subscription->plan}",
                    'custom_id' => (string) $subscription->id,
                ]],
                'application_context' => [
                    'return_url' => route('paypal.return'),
                    'cancel_url' => route('paypal.cancel'),
                    'brand_name' => 'Radar de Licitaciones',
                    'user_action' => 'PAY_NOW',
                ],
            ]);

        if ($response->failed()) {
            Log::error('[PayPal] Create order failed', ['body' => $response->body()]);

            return response()->json(['error' => 'Failed to create PayPal order'], 500);
        }

        $order = $response->json();

        return response()->json([
            'id' => $order['id'],
            'approve_url' => collect($order['links'])->firstWhere('rel', 'approve')['href'] ?? null,
        ]);
    }

    /**
     * Capture a PayPal order after user approval.
     */
    public function captureOrder(Request $request)
    {
        $orderId = $request->input('order_id');
        if (! $orderId) {
            return response()->json(['error' => 'Missing order_id'], 400);
        }

        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            return response()->json(['error' => 'PayPal authentication failed'], 500);
        }

        $response = Http::withToken($accessToken)
            ->post($this->apiUrl("/v2/checkout/orders/{$orderId}/capture"), []);

        if ($response->failed()) {
            Log::error('[PayPal] Capture failed', ['body' => $response->body()]);

            return response()->json(['error' => 'Failed to capture payment'], 500);
        }

        $data = $response->json();
        $capture = $data['purchase_units'][0]['payments']['captures'][0] ?? null;

        if (! $capture || $capture['status'] !== 'COMPLETED') {
            return response()->json(['error' => 'Payment not completed'], 400);
        }

        $subscriptionId = $data['purchase_units'][0]['custom_id'] ?? null;
        $subscription = Subscription::find($subscriptionId);

        if ($subscription) {
            SubscriptionService::recordPayment(
                $subscription,
                (float) $capture['amount']['value'],
                $capture['amount']['currency_code'],
                'paypal',
                $capture['id'],
            );
        }

        return response()->json(['status' => 'completed']);
    }

    /**
     * User returns from PayPal after approval.
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
     * PayPal webhook handler (no auth — verified by signature).
     */
    public function webhook(Request $request)
    {
        // Verify webhook signature
        if (! $this->verifyWebhook($request)) {
            Log::warning('[PayPal] Webhook signature verification failed');

            return response('Invalid signature', 401);
        }

        $event = $request->input('event_type');
        $resource = $request->input('resource', []);

        Log::info('[PayPal] Webhook received', ['event' => $event]);

        if ($event === 'PAYMENT.CAPTURE.COMPLETED') {
            $customId = $resource['custom_id'] ?? null;
            $subscription = Subscription::find($customId);

            if ($subscription) {
                SubscriptionService::recordPayment(
                    $subscription,
                    (float) ($resource['amount']['value'] ?? 0),
                    $resource['amount']['currency_code'] ?? 'USD',
                    'paypal',
                    $resource['id'] ?? null,
                    'Webhook: PAYMENT.CAPTURE.COMPLETED',
                );
            }
        }

        return response('OK', 200);
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
}
