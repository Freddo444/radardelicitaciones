<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $subscription = $user->subscription;

        if (! $subscription) {
            return redirect()->route('register.show')
                ->with('error', 'No tienes una suscripción. Regístrate primero.');
        }

        $usage = SubscriptionService::usage($subscription);
        $payments = $subscription->payments()->limit(10)->get();

        return view('billing.index', compact('subscription', 'usage', 'payments'));
    }

    public function cancel(Request $request)
    {
        $user = Auth::user();
        $subscription = $user->subscription;

        if (! $subscription || ! $user->isSubscriptionOwner()) {
            abort(403);
        }

        // Cancel on PayPal if it's a PayPal subscription
        if ($subscription->gateway_subscription_id && $subscription->payment_gateway === 'paypal') {
            $this->cancelPayPalSubscription($subscription->gateway_subscription_id);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Suscripción cancelada.');
    }

    private function cancelPayPalSubscription(string $paypalSubId): void
    {
        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.secret');

        if (! $clientId || ! $secret) {
            return;
        }

        $base = config('services.paypal.sandbox')
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';

        $tokenResponse = Http::asForm()
            ->withBasicAuth($clientId, $secret)
            ->post("{$base}/v1/oauth2/token", ['grant_type' => 'client_credentials']);

        $token = $tokenResponse->json('access_token');
        if (! $token) {
            return;
        }

        $response = Http::withToken($token)
            ->withBody(json_encode(['reason' => 'Customer requested cancellation']), 'application/json')
            ->post("{$base}/v1/billing/subscriptions/{$paypalSubId}/cancel");

        if ($response->failed()) {
            Log::error('[PayPal] Failed to cancel subscription', ['id' => $paypalSubId, 'body' => $response->body()]);
        }
    }
}
