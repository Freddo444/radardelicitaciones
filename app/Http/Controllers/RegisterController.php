<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function showTrialRegister()
    {
        return view('auth.register-trial');
    }

    public function storeTrial(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $user = DB::transaction(function () use ($request) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'email_verified_at' => now(),
                ]);

                Subscription::create([
                    'user_id' => $user->id,
                    'plan' => 'trial',
                    'status' => 'trialing',
                    'max_companies' => 1,
                    'max_users' => 2,
                    'monthly_amount' => 0,
                    'trial_ends_at' => now()->addDays(SubscriptionService::TRIAL_DAYS),
                    'trial_parse_count' => 0,
                    'trial_parse_limit' => SubscriptionService::TRIAL_PARSE_LIMIT,
                ]);

                return $user;
            });
        } catch (QueryException $e) {
            Log::error('[Register] Trial registration failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput($request->except('password', 'password_confirmation'))
                ->with('error', 'No se pudo crear tu cuenta en este momento. Intenta de nuevo.');
        }

        Auth::login($user);

        return redirect()->route('company-setup.show')
            ->with(array_filter([
                'success' => '¡Prueba gratuita activada! Configura tu primera empresa.',
                '_umami' => umami_flash_payload('trial_started'),
            ], fn ($v) => $v !== null));
    }

    /**
     * Step 1: Store plan in session and create PayPal subscription.
     */
    public function createOrder(Request $request)
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
            'register_plan' => [
                'max_companies' => $maxCompanies,
                'max_users' => $maxUsers,
                'amount' => $monthlyAmount,
                'billing_cycle' => $billingCycle,
            ],
        ]);

        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            return response()->json(['error' => 'Error de autenticación con PayPal.'], 500);
        }

        $planId = $billingCycle === 'annual'
            ? config('services.paypal.annual_plan_id')
            : config('services.paypal.plan_id');

        if (! $planId) {
            return response()->json(['error' => 'PayPal no está configurado para este ciclo. Contacta soporte.'], 500);
        }

        $payload = [
            'plan_id' => $planId,
            'application_context' => [
                'return_url' => route('register.paypal-return'),
                'cancel_url' => route('register.show'),
                'brand_name' => 'Radar de Licitaciones',
                'user_action' => 'SUBSCRIBE_NOW',
                'shipping_preference' => 'NO_SHIPPING',
            ],
        ];

        // Override price if different from base
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
            Log::error('[PayPal] Register create subscription failed', ['body' => $response->body()]);

            return response()->json(['error' => 'Error al crear la suscripción en PayPal.'], 500);
        }

        $sub = $response->json();
        $approveUrl = collect($sub['links'])->firstWhere('rel', 'approve')['href'] ?? null;

        session(['register_paypal_subscription_id' => $sub['id']]);

        return response()->json(['approve_url' => $approveUrl]);
    }

    /**
     * Step 2: PayPal returns here after user approves subscription.
     */
    public function paypalReturn(Request $request)
    {
        $querySubId = $request->query('subscription_id');
        $sessionSubId = session('register_paypal_subscription_id');
        $paypalSubId = $sessionSubId;
        $plan = session('register_plan');

        if ($querySubId && $sessionSubId && $querySubId !== $sessionSubId) {
            return redirect()->route('register.show')
                ->with('error', 'La sesión de pago no coincide. Intenta de nuevo.');
        }

        if (! $paypalSubId || ! $plan) {
            return redirect()->route('register.show')
                ->with('error', 'Sesión expirada. Intenta de nuevo.');
        }

        // Verify subscription status with PayPal
        $accessToken = $this->getAccessToken();
        if ($accessToken) {
            $response = Http::withToken($accessToken)
                ->get($this->apiUrl("/v1/billing/subscriptions/{$paypalSubId}"));

            if ($response->ok()) {
                $status = $response->json('status');
                if (! in_array($status, ['ACTIVE', 'APPROVED'])) {
                    Log::warning('[PayPal] Subscription not active on return', ['id' => $paypalSubId, 'status' => $status]);

                    return redirect()->route('register.show')
                        ->with('error', 'La suscripción no fue aprobada. Intenta de nuevo.');
                }
            }
        }

        session(['register_paypal_subscription_id' => $paypalSubId]);

        return redirect()->route('register.complete')
            ->with(array_filter([
                '_umami' => umami_flash_payload('registration_paypal_approved'),
            ], fn ($v) => $v !== null));
    }

    /**
     * Step 3: Show account creation form (payment already done).
     */
    public function showComplete()
    {
        if (! session('register_paypal_subscription_id') || ! session('register_plan')) {
            return redirect()->route('register.show');
        }

        return view('auth.register-complete');
    }

    /**
     * Step 4: Create user + subscription.
     */
    public function store(Request $request)
    {
        $plan = session('register_plan');
        $paypalSubId = session('register_paypal_subscription_id');

        if (! $plan || ! $paypalSubId) {
            return redirect()->route('register.show')
                ->with('error', 'Sesión expirada. Intenta de nuevo.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $billingCycle = $plan['billing_cycle'] ?? 'monthly';

        try {
            $user = DB::transaction(function () use ($request, $plan, $billingCycle, $paypalSubId) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);

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

                return $user;
            });
        } catch (QueryException $e) {
            Log::error('[Register] Paid registration failed', [
                'email' => $request->email,
                'paypal_subscription_id' => $paypalSubId,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput($request->except('password', 'password_confirmation'))
                ->with('error', 'No se pudo crear tu cuenta en este momento. Intenta de nuevo.');
        }

        session()->forget(['register_plan', 'register_paypal_subscription_id']);

        Auth::login($user);

        $user->sendEmailVerificationNotification();

        return redirect()->route('company-setup.show')
            ->with(array_filter([
                'success' => '¡Pago confirmado y cuenta creada! Configura tu primera empresa.',
                '_umami' => umami_flash_payload('registration_completed_paid', [
                    'billing_cycle' => $billingCycle,
                ]),
            ], fn ($v) => $v !== null));
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
