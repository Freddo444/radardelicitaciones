<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    /**
     * Step 1: Store plan in session and create PayPal subscription.
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'max_companies' => 'required|integer|min:1|max:10',
            'max_users' => 'required|integer|min:2|max:20',
        ]);

        $maxCompanies = (int) $request->max_companies;
        $maxUsers = (int) $request->max_users;
        $amount = SubscriptionService::calculateMonthly($maxCompanies, $maxUsers);

        session([
            'register_plan' => [
                'max_companies' => $maxCompanies,
                'max_users' => $maxUsers,
                'amount' => $amount,
            ],
        ]);

        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            return response()->json(['error' => 'Error de autenticación con PayPal.'], 500);
        }

        $planId = config('services.paypal.plan_id');
        if (! $planId) {
            return response()->json(['error' => 'PayPal no está configurado. Contacta soporte.'], 500);
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

        // Override price if different from base $45
        if ($amount != 45.00) {
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
        $paypalSubId = $request->query('subscription_id') ?? session('register_paypal_subscription_id');
        $plan = session('register_plan');

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

        return redirect()->route('register.complete');
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
            'payment_gateway' => 'paypal',
            'gateway_subscription_id' => $paypalSubId,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        session()->forget(['register_plan', 'register_paypal_subscription_id']);

        Auth::login($user);

        $user->sendEmailVerificationNotification();

        return redirect()->route('company-setup.show')
            ->with('success', '¡Pago confirmado y cuenta creada! Configura tu primera empresa.');
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
