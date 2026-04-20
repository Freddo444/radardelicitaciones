<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Billing\AzulCheckoutBuilder;
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
        $canPaypalProration = $subscription
            && $subscription->gateway_subscription_id
            && $subscription->payment_gateway === 'paypal';
        $canAzul = (bool) config('services.azul.merchant_id') && (bool) config('services.azul.auth_key');

        return view('billing.index', compact('subscription', 'usage', 'payments', 'isOwner', 'canPaypalProration', 'canAzul'));
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

    public function createAzulCheckout(Request $request, AzulCheckoutBuilder $builder)
    {
        $request->validate([
            'max_companies' => 'required|integer|min:1|max:10',
            'max_users' => 'required|integer|min:2|max:20',
            'billing_cycle' => 'sometimes|in:monthly,annual',
        ]);

        if (! config('services.azul.merchant_id') || ! config('services.azul.auth_key')) {
            return response()->json(['error' => 'Pago con Azul no esta configurado.'], 503);
        }

        $maxCompanies = (int) $request->max_companies;
        $maxUsers = (int) $request->max_users;
        $billingCycle = $request->input('billing_cycle', 'monthly');
        $monthlyAmount = SubscriptionService::calculateMonthly($maxCompanies, $maxUsers);
        $chargedUsd = SubscriptionService::calculatePrice($maxCompanies, $maxUsers, $billingCycle);

        session([
            'azul_intent' => 'subscribe',
            'subscribe_plan' => [
                'max_companies' => $maxCompanies,
                'max_users' => $maxUsers,
                'amount' => $monthlyAmount,
                'billing_cycle' => $billingCycle,
                'charged_usd' => $chargedUsd,
            ],
            'azul_checkout' => $builder->forChargedUsd($chargedUsd),
        ]);

        return response()->json(['checkout_url' => route('azul.checkout', [], true)]);
    }

    public function createAzulAddonCheckout(Request $request, AzulCheckoutBuilder $builder)
    {
        $request->validate(['type' => 'required|in:user,company']);

        if (! config('services.azul.merchant_id') || ! config('services.azul.auth_key')) {
            return response()->json(['error' => 'Pago con Azul no esta configurado.'], 503);
        }

        $user = Auth::user();
        $subscription = $user->subscription;

        if (! $subscription || ! $user->isSubscriptionOwner()) {
            abort(403);
        }

        if (! $subscription->isActive() || $subscription->status === 'trialing') {
            return response()->json(['error' => 'Suscripcion no activa.'], 422);
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

        if ($prorated <= 0) {
            return response()->json(['error' => 'Sin cobro prorrateado: confirma desde facturacion con el boton estandar.'], 422);
        }

        session([
            'azul_intent' => 'addon',
            'addon_plan' => [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'type' => $type,
                'new_companies' => $newCompanies,
                'new_users' => $newUsers,
                'new_monthly' => $newMonthly,
                'new_recurring' => $newRecurring,
                'prorated_usd' => $prorated,
            ],
            'azul_checkout' => $builder->forChargedUsd($prorated),
        ]);

        return response()->json(['checkout_url' => route('azul.checkout', [], true)]);
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
        if (! $accessToken) {
            Log::error('[PayPal] subscribeReturn: could not obtain access token');

            return redirect()->route('billing.subscribe')
                ->with('error', 'No se pudo verificar el pago con PayPal. Intenta de nuevo.');
        }

        $response = Http::withToken($accessToken)
            ->get($this->apiUrl("/v1/billing/subscriptions/{$paypalSubId}"));

        if (! $response->ok()) {
            Log::warning('[PayPal] subscribeReturn: subscription fetch failed', [
                'id' => $paypalSubId,
                'http_status' => $response->status(),
                'body' => $response->body(),
            ]);

            return redirect()->route('billing.subscribe')
                ->with('error', 'No se pudo confirmar la suscripcion en PayPal. Intenta de nuevo.');
        }

        $status = $response->json('status');
        if (! in_array($status, ['ACTIVE', 'APPROVED'], true)) {
            Log::warning('[PayPal] subscribeReturn: unexpected subscription status', [
                'id' => $paypalSubId,
                'status' => $status,
            ]);

            return redirect()->route('billing.subscribe')
                ->with('error', 'La suscripcion no fue aprobada.');
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
            ->with(array_filter([
                'success' => 'Suscripcion activada. Bienvenido a Radar de Licitaciones.',
                '_umami' => umami_flash_payload('subscription_activated', ['flow' => 'subscribe']),
            ], fn ($v) => $v !== null));
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
            ->with(array_filter([
                'success' => 'Suscripción cancelada. Tendrás acceso hasta el final del periodo actual.',
                '_umami' => umami_flash_payload('subscription_cancelled'),
            ], fn ($v) => $v !== null));
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
        $request->validate([
            'type' => 'required|in:user,company',
            'gateway' => 'sometimes|in:paypal,azul',
        ]);

        $user = Auth::user();
        $subscription = $user->subscription;

        if (! $subscription || ! $user->isSubscriptionOwner()) {
            abort(403);
        }

        if (! $subscription->isActive() || $subscription->status === 'trialing') {
            return back()->with('error', 'Necesitas una suscripción activa para agregar recursos.');
        }

        $type = $request->type;
        $gateway = $request->input('gateway', 'paypal');
        $addonPrice = $type === 'company'
            ? SubscriptionService::EXTRA_COMPANY_PRICE
            : SubscriptionService::EXTRA_USER_PRICE;

        $newCompanies = $subscription->max_companies + ($type === 'company' ? 1 : 0);
        $newUsers = $subscription->max_users + ($type === 'user' ? 1 : 0);
        $prorated = SubscriptionService::calculateProration($subscription, $addonPrice);
        $newMonthly = SubscriptionService::calculateMonthly($newCompanies, $newUsers);
        $newRecurring = SubscriptionService::calculatePrice($newCompanies, $newUsers, $subscription->billing_cycle ?? 'monthly');

        if ($prorated > 0 && $gateway === 'azul') {
            return back()->with('error', 'Para pagar el prorrateo con Azul usa el boton Tarjeta (Azul).');
        }

        if ($prorated > 0 && $gateway === 'paypal') {
            if (! $subscription->gateway_subscription_id || $subscription->payment_gateway !== 'paypal') {
                return back()->with('error', 'No se puede cobrar con PayPal en esta cuenta. Usa Azul o contacta soporte.');
            }
            $captured = $this->captureOneTimePayment($subscription, $prorated, $type);
            if (! $captured) {
                return back()->with('error', 'Error al procesar el cobro prorrateado con PayPal. Intenta de nuevo.');
            }
        }

        if ($subscription->gateway_subscription_id && $subscription->payment_gateway === 'paypal') {
            $this->revisePayPalSubscription($subscription->gateway_subscription_id, $newRecurring);
        }

        $subscription->update([
            'max_companies' => $newCompanies,
            'max_users' => $newUsers,
            'monthly_amount' => $newMonthly,
        ]);

        $label = $type === 'company' ? 'empresa' : 'usuario';
        $proratedNote = $prorated > 0
            ? "Cobro prorrateado: US\${$prorated}. "
            : '';

        return redirect()->route('billing.index')
            ->with(array_filter([
                'success' => "1 {$label} agregado(a). {$proratedNote}Próximo ciclo: US\$".number_format($newRecurring, 2).'/'.($subscription->billing_cycle === 'annual' ? 'año' : 'mes').'.',
                '_umami' => umami_flash_payload('subscription_addon_purchased', ['type' => $type]),
            ], fn ($v) => $v !== null));
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

    public function revisePayPalSubscriptionAmount(string $paypalSubId, float $newAmount): void
    {
        $this->revisePayPalSubscription($paypalSubId, $newAmount);
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
