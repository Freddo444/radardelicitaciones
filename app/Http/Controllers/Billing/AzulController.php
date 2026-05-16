<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Mail\AzulRegistrationRecovery;
use App\Models\Payment;
use App\Models\PendingRegistration;
use App\Models\Subscription;
use App\Services\Billing\AzulPaymentPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Sentry\Severity;

class AzulController extends Controller
{
    public function __construct(
        private AzulPaymentPageService $paymentPage,
    ) {}

    public function showCheckout(Request $request)
    {
        if (! $this->isAzulConfigured()) {
            return $this->redirectCheckoutMisconfigured();
        }

        $intent = (string) session('azul_intent', 'subscribe');
        $checkout = session('azul_checkout');

        if (! is_array($checkout) || ! $this->hasPlanForIntent($intent)) {
            return $this->redirectCheckoutExpired($intent);
        }

        $cancelUrl = $this->cancelUrlForIntent($intent);

        $fields = $this->paymentPage->buildSignedFormFields([
            'order_number' => $checkout['order_number'],
            'amount_cents' => (int) $checkout['amount_cents'],
            'itbis_cents' => (int) $checkout['itbis_cents'],
            'approved_url' => route('azul.callback', [], true),
            'declined_url' => route('azul.callback', [], true),
            'cancel_url' => $cancelUrl,
            'show_transaction_result' => '0',
            'locale' => 'ES',
        ]);

        $action = $this->paymentPage->paymentPageUrl();

        return response()->view('billing.azul-checkout', compact('action', 'fields'));
    }

    public function handleCallback(Request $request): RedirectResponse
    {
        if (! $this->isAzulConfigured()) {
            return $this->redirectCallbackMisconfigured();
        }

        $intent = (string) session('azul_intent', 'subscribe');
        $checkout = session('azul_checkout');

        if (! is_array($checkout) || ! $this->hasPlanForIntent($intent)) {
            return $this->redirectCallbackExpired($intent);
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

        if ($orderNumber === '' || $receivedHash === '') {
            return $this->redirectCallbackDeclined($intent, 'Respuesta de Azul incompleta.');
        }

        if ($orderNumber !== ($checkout['order_number'] ?? '')) {
            Log::warning('[Azul] OrderNumber mismatch', ['expected' => $checkout['order_number'] ?? null, 'got' => $orderNumber]);

            return $this->redirectCallbackDeclined($intent, 'La orden no coincide con tu sesion.');
        }

        if ($amount !== ($checkout['amount_str'] ?? '')) {
            Log::warning('[Azul] Amount mismatch', ['expected' => $checkout['amount_str'] ?? null, 'got' => $amount]);

            return $this->redirectCallbackDeclined($intent, 'El monto no coincide con tu sesion.');
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

            return $this->redirectCallbackDeclined($intent, 'No se pudo verificar la respuesta de Azul.');
        }

        if (strtoupper($isoCode) !== '00') {
            session()->forget(['azul_checkout', 'azul_intent']);

            \Sentry\captureMessage('Azul payment declined', Severity::warning(), [
                'extra' => [
                    'intent' => $intent,
                    'order_number' => $orderNumber,
                    'iso_code' => $isoCode,
                    'response_message' => $responseMessage,
                    'error_description' => $errorDescription,
                ],
            ]);

            return $this->redirectCallbackDeclined($intent, 'El pago fue declinado o no aprobado. Intenta con otra tarjeta o metodo de pago.', true);
        }

        $cardLastFour = self::cardLastFourFromAzulMask((string) $request->query('CardNumber', ''));

        return match ($intent) {
            'addon' => $this->completeAddonPayment($orderNumber, $isoCode, $authorizationCode, $rrn, $cardLastFour),
            'register' => $this->completeRegisterPayment($orderNumber, $isoCode, $authorizationCode, $rrn, $cardLastFour),
            default => $this->completeSubscribePayment($orderNumber, $isoCode, $authorizationCode, $rrn, $cardLastFour),
        };
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('[Azul] Webhook', ['payload' => $payload]);

        $orderNumber = (string) ($payload['OrderNumber'] ?? '');
        $isoCode = strtoupper((string) ($payload['IsoCode'] ?? $payload['ISOCode'] ?? ''));

        if ($orderNumber === '' || $isoCode !== '00') {
            return response('OK', 200);
        }

        $authKey = (string) config('services.azul.auth_key');
        if (! empty($payload['AuthHash']) && $authKey) {
            $valid = $this->paymentPage->verifyResponseAuthHash(
                $orderNumber,
                (string) ($payload['Amount'] ?? ''),
                (string) ($payload['AuthorizationCode'] ?? ''),
                (string) ($payload['DateTime'] ?? ''),
                (string) ($payload['ResponseCode'] ?? ''),
                $isoCode,
                (string) ($payload['ResponseMessage'] ?? ''),
                (string) ($payload['ErrorDescription'] ?? ''),
                (string) ($payload['RRN'] ?? ''),
                $authKey,
                (string) $payload['AuthHash'],
            );

            if (! $valid) {
                Log::warning('[Azul] Webhook AuthHash invalid', ['order' => $orderNumber]);

                return response('OK', 200);
            }
        }

        if (PendingRegistration::where('order_number', $orderNumber)->exists()) {
            return response('OK', 200);
        }

        // Browser died before the callback redirect completed — record the orphan
        PendingRegistration::create([
            'order_number' => $orderNumber,
            'rrn' => ($payload['RRN'] ?? '') ?: null,
            'auth_code' => ($payload['AuthorizationCode'] ?? '') ?: null,
            'iso_code' => $isoCode,
            'card_last_four' => self::cardLastFourFromAzulMask((string) ($payload['CardNumber'] ?? '')),
            'plan' => [],
            'expires_at' => now()->addHours(48),
        ]);

        \Sentry\captureMessage('[Azul] Orphan payment recorded via webhook — admin reconciliation needed', Severity::warning(), [
            'extra' => ['order_number' => $orderNumber],
        ]);

        Log::warning('[Azul] Webhook: orphan payment recorded', ['order' => $orderNumber]);

        return response('OK', 200);
    }

    private static function cardLastFourFromAzulMask(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);
        if (strlen($digits) < 4) {
            return null;
        }

        return substr($digits, -4);
    }

    private function completeSubscribePayment(string $orderNumber, string $isoCode, string $authorizationCode, string $rrn, ?string $cardLastFour): RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            session()->forget(['subscribe_plan', 'azul_checkout', 'azul_intent']);

            return redirect()->route('login')->with('error', 'Inicia sesion para finalizar.');
        }

        $plan = session('subscribe_plan');
        if (! is_array($plan)) {
            session()->forget(['subscribe_plan', 'azul_checkout', 'azul_intent']);

            return redirect()->route('billing.subscribe')->with('error', 'Sesion expirada. Intenta de nuevo.');
        }

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
            'card_last_four' => $cardLastFour,
            'status' => 'completed',
            'paid_at' => now(),
            'notes' => 'Azul — IsoCode '.$isoCode.($authorizationCode !== '' ? ' — Auth '.$authorizationCode : ''),
        ]);

        session()->forget(['subscribe_plan', 'azul_checkout', 'azul_intent']);

        return redirect()->route('billing.index')
            ->with(array_filter([
                'success' => 'Suscripcion activada. Bienvenido a Radar de Licitaciones.',
                '_umami' => umami_flash_payload('subscription_activated', ['flow' => 'subscribe_azul']),
            ], fn ($v) => $v !== null));
    }

    private function completeAddonPayment(string $orderNumber, string $isoCode, string $authorizationCode, string $rrn, ?string $cardLastFour): RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            session()->forget(['addon_plan', 'azul_checkout', 'azul_intent']);

            return redirect()->route('login')->with('error', 'Inicia sesion para finalizar.');
        }

        $addon = session('addon_plan');
        if (! is_array($addon)) {
            session()->forget(['addon_plan', 'azul_checkout', 'azul_intent']);

            return redirect()->route('billing.index')->with('error', 'Sesion expirada. Intenta de nuevo.');
        }

        $subscription = Subscription::find($addon['subscription_id'] ?? 0);
        if (! $subscription || (int) $subscription->user_id !== (int) $user->id || (int) ($addon['user_id'] ?? 0) !== (int) $user->id) {
            session()->forget(['addon_plan', 'azul_checkout', 'azul_intent']);

            return redirect()->route('billing.index')->with('error', 'Suscripcion no valida.');
        }

        $prorated = (float) ($addon['prorated_usd'] ?? 0);

        $type = (string) ($addon['type'] ?? '');
        if (! in_array($type, ['user', 'company'], true)) {
            session()->forget(['addon_plan', 'azul_checkout', 'azul_intent']);

            return redirect()->route('billing.index')->with('error', 'Tipo de complemento invalido.');
        }

        Payment::create([
            'subscription_id' => $subscription->id,
            'amount' => $prorated,
            'currency' => 'USD',
            'gateway' => 'azul',
            'gateway_payment_id' => $rrn !== '' ? $rrn : $authorizationCode,
            'card_last_four' => $cardLastFour,
            'status' => 'completed',
            'paid_at' => now(),
            'notes' => 'Azul prorrateo — +1 '.($type === 'company' ? 'empresa' : 'usuario').' — IsoCode '.$isoCode,
        ]);

        if ($subscription->gateway_subscription_id && $subscription->payment_gateway === 'paypal') {
            app(SubscriptionController::class)->revisePayPalSubscriptionAmount(
                $subscription->gateway_subscription_id,
                (float) ($addon['new_recurring'] ?? 0)
            );
        }

        $subscription->update([
            'max_companies' => (int) ($addon['new_companies'] ?? $subscription->max_companies),
            'max_users' => (int) ($addon['new_users'] ?? $subscription->max_users),
            'monthly_amount' => (float) ($addon['new_monthly'] ?? $subscription->monthly_amount),
        ]);

        session()->forget(['addon_plan', 'azul_checkout', 'azul_intent']);

        $label = $type === 'company' ? 'empresa' : 'usuario';
        $newRecurring = (float) ($addon['new_recurring'] ?? 0);

        return redirect()->route('billing.index')
            ->with(array_filter([
                'success' => "1 {$label} agregado(a). Cobro prorrateado: US\$".number_format($prorated, 2).'. Próximo ciclo: US\$'.number_format($newRecurring, 2).'/'.($subscription->billing_cycle === 'annual' ? 'año' : 'mes').'.',
                '_umami' => umami_flash_payload('subscription_addon_purchased', ['type' => $type, 'gateway' => 'azul']),
            ], fn ($v) => $v !== null));
    }

    private function completeRegisterPayment(string $orderNumber, string $isoCode, string $authorizationCode, string $rrn, ?string $cardLastFour): RedirectResponse
    {
        $plan = session('register_plan');
        if (! is_array($plan)) {
            session()->forget(['register_plan', 'azul_checkout', 'azul_intent']);

            return redirect()->route('register.show')->with('error', 'Sesion expirada. Intenta de nuevo.');
        }

        $pending = PendingRegistration::firstOrCreate(
            ['order_number' => $orderNumber],
            [
                'rrn' => $rrn ?: null,
                'auth_code' => $authorizationCode ?: null,
                'iso_code' => $isoCode,
                'card_last_four' => $cardLastFour,
                'plan' => $plan,
                'intended_email' => session('register_intended_email'),
                'expires_at' => now()->addHours(48),
            ]
        );

        if ($pending->wasRecentlyCreated && $pending->intended_email) {
            try {
                Mail::queue(new AzulRegistrationRecovery($pending));
            } catch (\Throwable $e) {
                Log::warning('[Azul] Failed to queue registration recovery email', [
                    'order' => $orderNumber,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        session([
            'register_pending_id' => $pending->id,
            'register_azul_order_number' => $orderNumber,
            'register_azul_iso' => $isoCode,
            'register_azul_auth' => $authorizationCode,
            'register_azul_rrn' => $rrn,
            'register_azul_card_last_four' => $cardLastFour,
        ]);

        session()->forget(['azul_checkout', 'azul_intent']);

        return redirect()->route('register.complete')
            ->with(array_filter([
                'success' => 'Pago con Azul confirmado. Completa tu cuenta.',
                '_umami' => umami_flash_payload('registration_azul_approved'),
            ], fn ($v) => $v !== null));
    }

    private function hasPlanForIntent(string $intent): bool
    {
        return match ($intent) {
            'subscribe' => is_array(session('subscribe_plan')),
            'register' => is_array(session('register_plan')),
            'addon' => is_array(session('addon_plan')),
            default => false,
        };
    }

    private function cancelUrlForIntent(string $intent): string
    {
        return match ($intent) {
            'register' => route('register.show', [], true),
            'addon' => route('billing.index', [], true),
            default => route('billing.subscribe', [], true),
        };
    }

    private function redirectCheckoutMisconfigured(): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('billing.subscribe')->with('error', 'Pago con Azul no esta configurado.');
        }

        return redirect()->route('register.show')->with('error', 'Pago con Azul no esta configurado.');
    }

    private function redirectCheckoutExpired(string $intent): RedirectResponse
    {
        return match ($intent) {
            'register' => redirect()->route('register.show')->with('error', 'Sesion expirada. Intenta de nuevo.'),
            'addon' => redirect()->route('billing.index')->with('error', 'Sesion expirada. Intenta de nuevo.'),
            default => redirect()->route('billing.subscribe')->with('error', 'Sesion expirada. Intenta de nuevo.'),
        };
    }

    private function redirectCallbackMisconfigured(): RedirectResponse
    {
        $intent = (string) session('azul_intent', 'subscribe');

        return match ($intent) {
            'register' => redirect()->route('register.show')->with('error', 'Pago con Azul no esta configurado.'),
            'addon' => Auth::check()
                ? redirect()->route('billing.index')->with('error', 'Pago con Azul no esta configurado.')
                : redirect()->route('login')->with('error', 'Pago con Azul no esta configurado.'),
            default => Auth::check()
                ? redirect()->route('billing.subscribe')->with('error', 'Pago con Azul no esta configurado.')
                : redirect()->route('login')->with('error', 'Pago con Azul no esta configurado.'),
        };
    }

    private function redirectCallbackExpired(string $intent): RedirectResponse
    {
        return $this->redirectCheckoutExpired($intent);
    }

    private function redirectCallbackDeclined(string $intent, string $message, bool $warning = false): RedirectResponse
    {
        session()->forget(['azul_checkout', 'azul_intent']);

        $redirect = match ($intent) {
            'register' => redirect()->route('register.show'),
            'addon' => redirect()->route('billing.index'),
            default => redirect()->route('billing.subscribe'),
        };

        return $warning ? $redirect->with('warning', $message) : $redirect->with('error', $message);
    }

    private function isAzulConfigured(): bool
    {
        return (bool) config('services.azul.merchant_id') && (bool) config('services.azul.auth_key');
    }
}
