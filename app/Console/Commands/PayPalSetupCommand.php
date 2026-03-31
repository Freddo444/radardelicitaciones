<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class PayPalSetupCommand extends Command
{
    protected $signature = 'paypal:setup';

    protected $description = 'Create PayPal product and billing plan (run once)';

    public function handle(): int
    {
        $token = $this->getAccessToken();
        if (! $token) {
            $this->error('Failed to authenticate with PayPal. Check PAYPAL_CLIENT_ID and PAYPAL_SECRET.');

            return 1;
        }

        $base = $this->apiBase();
        $this->info("Using PayPal ".($this->isSandbox() ? 'SANDBOX' : 'LIVE'));

        // 1. Create product
        $this->info('Creating product...');
        $product = Http::withToken($token)
            ->post("{$base}/v1/catalogs/products", [
                'name' => 'Radar de Licitaciones',
                'description' => 'Monitoreo inteligente de licitaciones públicas de la República Dominicana',
                'type' => 'SERVICE',
                'category' => 'SOFTWARE',
            ]);

        if ($product->failed()) {
            $this->error('Failed to create product: '.$product->body());

            return 1;
        }

        $productId = $product->json('id');
        $this->info("Product created: {$productId}");

        // 2. Create billing plan
        $this->info('Creating billing plan...');
        $plan = Http::withToken($token)
            ->post("{$base}/v1/billing/plans", [
                'product_id' => $productId,
                'name' => 'Plan Mensual',
                'description' => 'Suscripción mensual a Radar de Licitaciones',
                'billing_cycles' => [
                    [
                        'frequency' => [
                            'interval_unit' => 'MONTH',
                            'interval_count' => 1,
                        ],
                        'tenure_type' => 'REGULAR',
                        'sequence' => 1,
                        'total_cycles' => 0,
                        'pricing_scheme' => [
                            'fixed_price' => [
                                'value' => '45',
                                'currency_code' => 'USD',
                            ],
                        ],
                    ],
                ],
                'payment_preferences' => [
                    'auto_bill_outstanding' => true,
                    'payment_failure_threshold' => 3,
                ],
            ]);

        if ($plan->failed()) {
            $this->error('Failed to create plan: '.$plan->body());

            return 1;
        }

        $planId = $plan->json('id');
        $this->info("Plan created: {$planId}");

        $this->newLine();
        $this->warn('Add these to your .env:');
        $this->line("PAYPAL_PLAN_ID={$planId}");
        $this->newLine();
        $this->info('Done! Now configure the webhook in PayPal dashboard with these events:');
        $this->line('  - BILLING.SUBSCRIPTION.ACTIVATED');
        $this->line('  - BILLING.SUBSCRIPTION.CANCELLED');
        $this->line('  - BILLING.SUBSCRIPTION.SUSPENDED');
        $this->line('  - PAYMENT.SALE.COMPLETED');

        return 0;
    }

    private function getAccessToken(): ?string
    {
        $response = Http::asForm()
            ->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.secret'))
            ->post($this->apiBase().'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        return $response->json('access_token');
    }

    private function isSandbox(): bool
    {
        return (bool) config('services.paypal.sandbox');
    }

    private function apiBase(): string
    {
        return $this->isSandbox()
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }
}
