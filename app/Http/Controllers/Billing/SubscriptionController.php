<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
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
        $usage = $subscription ? SubscriptionService::usage($subscription) : null;
        $payments = $subscription ? $subscription->payments()->latest()->limit(10)->get() : collect();
        $isOwner = $user->isSubscriptionOwner();

        return view('billing.index', compact('subscription', 'usage', 'payments', 'isOwner'));
    }

    public function showSubscribe()
    {
        $user = Auth::user();
        $subscription = $user->subscription;

        if ($subscription && $subscription->isActive() && ! $subscription->trialExpired()) {
            return redirect()->route('billing.index');
        }

        return view('billing.subscribe');
    }

    public function createSubscription(Request $request)
    {
        $request->validate([
            'max_companies' => 'required|integer|min:1|max:10',
            'max_users' => 'required|integer|min:2|max:20',
            'billing_cycle' => 'sometimes|in:monthly,annual',
        ]);

        $maxCompanies = (int) $request->max_companies;
        $maxUsers = (int) $request->max_users;
        $billingCycle = $request->input('billing_cycle', 'monthly');
        $monthlyAmount = SubscriptionService::calculateMonthly($maxCompanies, $maxUsers);
        $amount = SubscriptionService::calculatePrice($maxCompanies, $maxUsers, $billingCycle);

        session([
            'subscribe_plan' => [
                'max_companies' => $maxCompanies,
                'max_users' => $maxUsers,
                'amount' => $monthlyAmount,
                'billing_cycle' => $billingCycle,
            ],
        ]);

        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            return response()->json(['error' => 'Error de autenticacion con PayPal.'], 500);
        }

        $planId = $billingCycle === 'annual'
            ? config('services.paypal.annual_plan_id')
            : config('services.paypal.plan_id');

        if (! $planId) {
            return response()->json(['error' => 'PayPal no esta configurado para este ciclo.'], 500);
        }

        $payload = [
            'plan_id' => $planId,
            'application_context' => [
                'return_url' => route('billing.subscribe.return'),
                'cancel_url' => route('billing.subscribe'),
                'brand_name' => 'Radar de Licitaciones',
                'user_action' => 'SUBSCRIBE_NOW',
                'shipping_preference' => 'NO_SHIPPING',
            ],
        ];

        $baseAmount = $billingCycle === 'annual'
            ? SubscriptionService::calculateAnnual()
            : SubscriptionService::calculateMonthly();

        if ($amount != $baseAmount) {
            $payload['plan'] = [
                'billing_cycles' => [
                    [
                        'sequence' => 1,
                        'pricing_scheme' => [
                            'fixed_price' => [
                                'value' => number_format($amount, 2, '.', ''),
                                'currency_code' => 'USD',
                            ],
                        ],
                    ],
                ],
            ];
        }

        $response = Http::withToken($accessToken)
            ->post($this->apiUrl('/v1/billing/subscriptions'), $payload);

        if ($response->failed()) {
            Log::error('[PayPal] Subscribe create failed', ['body' => $response->body()]);

            return response()->json(['error' => 'Error al crear la suscripcion en PayPal.'], 500);
        }

        $sub = $response->json();
        $approveUrl = collect($sub['links'])->firstWhere('rel', 'approve')['href'] ?? null;

        session(['subscribe_paypal_id' => $sub['id']]);

        return response()->json(['approve_url' => $approveUrl]);
    }

    public function subscribeReturn(Request $request)
    {
        $paypalSubId = $request->query('subscription_id') ?? session('subscribe_paypal_id');
        $plan = session('subscribe_plan');

        if (! $paypalSubId || ! $plan) {
            return redirect()->route('billing.subscribe')
                ->with('error', 'Sesion expirada. Intenta de nuevo.');
        }

        $accessToken = $this->getAccessToken();
        if ($accessToken) {
            $response = Http::withToken($accessToken)
                ->get($this->apiUrl("/v1/billing/subscriptions/{$paypalSubId}"));

            if ($response->ok()) {
                $status = $response->json('status');
                if (! in_array($status, ['ACTIVE', 'APPROVED'])) {
                    return redirect()->route('billing.subscribe')
                        ->with('error', 'La suscripcion no fue aprobada.');
                }
            }
        }

        $user = Auth::user();
        $subscription = $user->subscription;
        $billingCycle = $plan['billing_cycle'] ?? 'monthly';

        if ($subscription) {
            $subscription->update([
                'plan' => 'basic',
                'status' => 'active',
                'max_companies' => $plan['max_companies'],
                'max_users' => $plan['max_users'],
                'monthly_amount' => $plan['amount'],
                'billing_cycle' => $billingCycle,
                'payment_gateway' => 'paypal',
                'gateway_subscription_id' => $paypalSubId,
                'current_period_start' => now(),
                'current_period_end' => $billingCycle === 'annual' ? now()->addYear() : now()->addMonth(),
                'trial_ends_at' => null,
            ]);
        } else {
            Subscription::create([
                'user_id' => $user->id,
                'plan' => 'basic',
                'status' => 'active',
                'max_companies' => $plan['max_companies'],
                'max_users' => $plan['max_users'],
                'monthly_amount' => $plan['amount'],
                'billing_cycle' => $billingCycle,
                'payment_gateway' => 'paypal',
                'gateway_subscription_id' => $paypalSubId,
                'current_period_start' => now(),
                'current_period_end' => $billingCycle === 'annual' ? now()->addYear() : now()->addMonth(),
            ]);
        }

        session()->forget(['subscribe_plan', 'subscribe_paypal_id']);

        return redirect()->route('billing.index')
            ->with('success', 'Suscripcion activada. Bienvenido a Radar de Licitaciones.');
    }

    public function cancel(Request $request)
    {
        $user = Auth::user();
        $subscription = $user->subscription;

        if (! $subscription || ! $user->isSubscriptionOwner()) {
            abort(403);
        }

        if ($subscription->gateway_subscription_id && $subscription->payment_gateway === 'paypal') {
            $this->cancelPayPalSubscription($subscription->gateway_subscription_id);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return redirect()->route('billing.index')
            ->with('success', 'Suscripción cancelada. Tendrás acceso hasta el final del periodo actual.');
    }

    /**
     * Preview proration for adding a user or company.
     */
    public function previewAddon(Request $request)
    {
        $request->validate(['type' => 'required|in:user,company']);

        $subscription = Auth::user()->subscription;
        if (! $subscription || ! $subscription->isActive() || $subscription->status === 'trialing') {
            return response()->json(['error' => 'Suscripción no activa.'], 422);
        }

        $addonPrice = $request->type === 'company'
            ? SubscriptionService::EXTRA_COMPANY_PRICE
            : SubscriptionService::EXTRA_USER_PRICE;

        $prorated = SubscriptionService::calculateProration($subscription, $addonPrice);

        $newCompanies = $subscription->max_companies + ($request->type === 'company' ? 1 : 0);
        $newUsers = $subscription->max_users + ($request->type === 'user' ? 1 : 0);
        $newMonthly = SubscriptionService::calculateMonthly($newCompanies, $newUsers);
        $newRecurring = SubscriptionService::calculatePrice($newCompanies, $newUsers, $subscription->billing_cycle ?? 'monthly');

        $periodEnd = $subscription->current_period_end?->format('d/m/Y') ?? '—';

        return response()->json([
            'prorated_amount' => $prorated,
            'addon_monthly' => $addonPrice,
            'new_monthly' => $newMonthly,
            'new_recurring' => $newRecurring,
            'billing_cycle' => $subscription->billing_cycle ?? 'monthly',
            'period_end' => $periodEnd,
        ]);
    }

    /**
     * Purchase an additional user or company with prorated charge.
     */
    public function purchaseAddon(Request $request)
    {
        $request->validate(['type' => 'required|in:user,company']);

        $user = Auth::user();
        $subscription = $user->subscription;

        if (! $subscription || ! $user->isSubscriptionOwner()) {
            abort(403);
        }

        if (! $subscription->isActive() || $subscription->status === 'trialing') {
            return back()->with('error', 'Necesitas una suscripción activa para agregar recursos.');
        }

        $type = $request->type;
        $addonPrice = $type === 'company'
            ? SubscriptionService::EXTRA_COMPANY_PRICE
            : SubscriptionService::EXTRA_USER_PRICE;

        $newCompanies = $subscription->max_companies + ($type === 'company' ? 1 : 0);
        $newUsers = $subscription->max_users + ($type === 'user' ? 1 : 0);
        $prorated = SubscriptionService::calculateProration($subscription, $addonPrice);
        $newMonthly = SubscriptionService::calculateMonthly($newCompanies, $newUsers);
        $newRecurring = SubscriptionService::calculatePrice($newCompanies, $newUsers, $subscription->billing_cycle ?? 'monthly');

        // 1. Capture prorated one-time payment via PayPal
        if ($prorated > 0 && $subscription->gateway_subscription_id && $subscription->payment_gateway === 'paypal') {
            $captured = $this->captureOneTimePayment($subscription, $prorated, $type);
            if (! $captured) {
                return back()->with('error', 'Error al procesar el cobro prorrateado con PayPal. Intenta de nuevo.');
            }
        }

        // 2. Revise PayPal subscription recurring amount for next cycle
        if ($subscription->gateway_subscription_id && $subscription->payment_gateway === 'paypal') {
            $this->revisePayPalSubscription($subscription->gateway_subscription_id, $newRecurring);
        }

        // 3. Update local subscription limits
        $subscription->update([
            'max_companies' => $newCompanies,
            'max_users' => $newUsers,
            'monthly_amount' => $newMonthly,
        ]);

        $label = $type === 'company' ? 'empresa' : 'usuario';

        return redirect()->route('billing.index')
            ->with('success', "1 {$label} agregado(a). Cobro prorrateado: US\${$prorated}. Próximo ciclo: US\$".number_format($newRecurring, 2).'/'.($subscription->billing_cycle === 'annual' ? 'año' : 'mes').'.');
    }

    /**
     * Capture a one-time prorated payment via PayPal subscription.
     */
    private function captureOneTimePayment(mixed $subscription, float $amount, string $type): bool
    {
        $token = $this->getAccessToken();
        if (! $token) {
            return false;
        }

        $response = Http::withToken($token)
            ->post($this->apiUrl("/v1/billing/subscriptions/{$subscription->gateway_subscription_id}/capture"), [
                'note' => 'Prorated charge for additional '.($type === 'company' ? 'company' : 'user'),
                'capture_type' => 'OUTSTANDING_BALANCE',
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format($amount, 2, '.', ''),
                ],
            ]);

        if ($response->failed()) {
            Log::error('[PayPal] Failed to capture prorated payment', [
                'subscription' => $subscription->id,
                'amount' => $amount,
                'body' => $response->body(),
            ]);

            return false;
        }

        Payment::create([
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'currency' => 'USD',
            'gateway' => 'paypal',
            'gateway_payment_id' => $response->json('id'),
            'status' => 'completed',
            'paid_at' => now(),
            'notes' => 'Prorated: +1 '.($type === 'company' ? 'empresa' : 'usuario'),
        ]);

        return true;
    }

    /**
     * Revise PayPal subscription recurring amount for next billing cycle.
     */
    private function revisePayPalSubscription(string $paypalSubId, float $newAmount): void
    {
        $token = $this->getAccessToken();
        if (! $token) {
            return;
        }

        $response = Http::withToken($token)
            ->post($this->apiUrl("/v1/billing/subscriptions/{$paypalSubId}/revise"), [
                'plan' => [
                    'billing_cycles' => [
                        [
                            'sequence' => 1,
                            'pricing_scheme' => [
                                'fixed_price' => [
                                    'value' => number_format($newAmount, 2, '.', ''),
                                    'currency_code' => 'USD',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            Log::error('[PayPal] Failed to revise subscription amount', [
                'id' => $paypalSubId,
                'new_amount' => $newAmount,
                'body' => $response->body(),
            ]);
        }
    }

    private function cancelPayPalSubscription(string $paypalSubId): void
    {
        $token = $this->getAccessToken();
        if (! $token) {
            return;
        }

        $response = Http::withToken($token)
            ->withBody(json_encode(['reason' => 'Customer requested cancellation']), 'application/json')
            ->post($this->apiUrl("/v1/billing/subscriptions/{$paypalSubId}/cancel"));

        if ($response->failed()) {
            Log::error('[PayPal] Failed to cancel subscription', ['id' => $paypalSubId, 'body' => $response->body()]);
        }
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
            ->post($this->apiUrl('/v1/oauth2/token'), ['grant_type' => 'client_credentials']);

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
