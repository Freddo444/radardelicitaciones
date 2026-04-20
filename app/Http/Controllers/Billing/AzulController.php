<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Billing\AzulPaymentPageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AzulController extends Controller
{
    public function __construct(
        private AzulPaymentPageService $paymentPage,
    ) {}

    public function showCheckout(Request $request)
    {
        if (! $this->isAzulConfigured()) {
            return redirect()->route('billing.subscribe')
                ->with('error', 'Pago con Azul no esta configurado.');
        }

        $checkout = session('azul_checkout');
        $plan = session('subscribe_plan');

        if (! is_array($checkout) || ! is_array($plan)) {
            return redirect()->route('billing.subscribe')
                ->with('error', 'Sesion expirada. Intenta de nuevo.');
        }

        $fields = $this->paymentPage->buildSignedFormFields([
            'order_number' => $checkout['order_number'],
            'amount_cents' => (int) $checkout['amount_cents'],
            'itbis_cents' => (int) $checkout['itbis_cents'],
            'approved_url' => route('azul.callback', [], true),
            'declined_url' => route('azul.callback', [], true),
            'cancel_url' => route('billing.subscribe', [], true),
            'show_transaction_result' => '0',
            'locale' => 'ES',
        ]);

        $action = $this->paymentPage->paymentPageUrl();

        return response()->view('billing.azul-checkout', compact('action', 'fields'));
    }

    public function handleCallback(Request $request)
    {
        if (! $this->isAzulConfigured()) {
            return redirect()->route('billing.subscribe')
                ->with('error', 'Pago con Azul no esta configurado.');
        }

        $authKey = (string) config('services.azul.auth_key');

        $orderNumber = (string) $request->query('OrderNumber', '');
        $amount = (string) $request->query('Amount', '');
        $authorizationCode = (string) $request->query('AuthorizationCode', '');
        $dateTime = (string) $request->query('DateTime', '');
        $responseCode = (string) $request->query('ResponseCode', '');
        $isoCode = (string) $request->query('IsoCode', $request->query('ISOCode', ''));
        $responseMessage = (string) $request->query('ResponseMessage', '');
        $errorDescription = (string) $request->query('ErrorDescription', '');
        $rrn = (string) $request->query('RRN', '');
        $receivedHash = (string) $request->query('AuthHash', '');

        $checkout = session('azul_checkout');
        $plan = session('subscribe_plan');

        if (! is_array($checkout) || ! is_array($plan)) {
            return redirect()->route('billing.subscribe')
                ->with('error', 'Sesion expirada. Intenta de nuevo.');
        }

        if ($orderNumber === '' || $receivedHash === '') {
            return redirect()->route('billing.subscribe')
                ->with('error', 'Respuesta de Azul incompleta.');
        }

        if ($orderNumber !== ($checkout['order_number'] ?? '')) {
            Log::warning('[Azul] OrderNumber mismatch', ['expected' => $checkout['order_number'] ?? null, 'got' => $orderNumber]);

            return redirect()->route('billing.subscribe')
                ->with('error', 'La orden no coincide con tu sesion.');
        }

        if ($amount !== ($checkout['amount_str'] ?? '')) {
            Log::warning('[Azul] Amount mismatch', ['expected' => $checkout['amount_str'] ?? null, 'got' => $amount]);

            return redirect()->route('billing.subscribe')
                ->with('error', 'El monto no coincide con tu sesion.');
        }

        if (! $this->paymentPage->verifyResponseAuthHash(
            $orderNumber,
            $amount,
            $authorizationCode,
            $dateTime,
            $responseCode,
            $isoCode,
            $responseMessage,
            $errorDescription,
            $rrn,
            $authKey,
            $receivedHash,
        )) {
            Log::warning('[Azul] AuthHash verification failed', ['order' => $orderNumber]);

            return redirect()->route('billing.subscribe')
                ->with('error', 'No se pudo verificar la respuesta de Azul.');
        }

        if (strtoupper($isoCode) !== '00') {
            session()->forget(['subscribe_plan', 'azul_checkout']);

            return redirect()->route('billing.subscribe')
                ->with('warning', 'El pago fue declinado o no aprobado. Intenta con otra tarjeta o metodo de pago.');
        }

        $user = Auth::user();
        $subscription = $user->subscription;
        $billingCycle = $plan['billing_cycle'] ?? 'monthly';
        $chargedUsd = (float) ($plan['charged_usd'] ?? 0);

        if ($subscription) {
            $subscription->update([
                'plan' => 'basic',
                'status' => 'active',
                'max_companies' => $plan['max_companies'],
                'max_users' => $plan['max_users'],
                'monthly_amount' => $plan['amount'],
                'billing_cycle' => $billingCycle,
                'payment_gateway' => 'azul',
                'gateway_subscription_id' => $orderNumber,
                'current_period_start' => now(),
                'current_period_end' => $billingCycle === 'annual' ? now()->addYear() : now()->addMonth(),
                'trial_ends_at' => null,
            ]);
        } else {
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan' => 'basic',
                'status' => 'active',
                'max_companies' => $plan['max_companies'],
                'max_users' => $plan['max_users'],
                'monthly_amount' => $plan['amount'],
                'billing_cycle' => $billingCycle,
                'payment_gateway' => 'azul',
                'gateway_subscription_id' => $orderNumber,
                'current_period_start' => now(),
                'current_period_end' => $billingCycle === 'annual' ? now()->addYear() : now()->addMonth(),
            ]);
        }

        Payment::create([
            'subscription_id' => $subscription->id,
            'amount' => $chargedUsd,
            'currency' => 'USD',
            'gateway' => 'azul',
            'gateway_payment_id' => $rrn !== '' ? $rrn : $authorizationCode,
            'status' => 'completed',
            'paid_at' => now(),
            'notes' => 'Azul — IsoCode '.$isoCode.($authorizationCode !== '' ? ' — Auth '.$authorizationCode : ''),
        ]);

        session()->forget(['subscribe_plan', 'azul_checkout']);

        return redirect()->route('billing.index')
            ->with(array_filter([
                'success' => 'Suscripcion activada. Bienvenido a Radar de Licitaciones.',
                '_umami' => umami_flash_payload('subscription_activated', ['flow' => 'subscribe_azul']),
            ], fn ($v) => $v !== null));
    }

    public function handleWebhook(Request $request)
    {
        Log::info('[Azul] Webhook', ['payload' => $request->all()]);

        return response('OK', 200);
    }

    private function isAzulConfigured(): bool
    {
        return (bool) config('services.azul.merchant_id') && (bool) config('services.azul.auth_key');
    }
}
