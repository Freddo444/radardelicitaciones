<?php

namespace Tests\Feature;

use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class InvoiceDownloadTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private function completedPaymentFor($subscription): Payment
    {
        return Payment::create([
            'subscription_id' => $subscription->id,
            'amount' => 45,
            'currency' => 'USD',
            'gateway' => 'paypal',
            'status' => 'completed',
            'paid_at' => now(),
        ]);
    }

    public function test_owner_can_download_own_invoice_pdf(): void
    {
        [$user, $sub] = $this->makeOwnerWithCompany();
        $payment = $this->completedPaymentFor($sub);

        $this->actingAs($user)->get(route('billing.invoice', $payment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_owner_cannot_download_another_tenants_invoice(): void
    {
        [, $subA] = $this->makeOwnerWithCompany();
        $foreignPayment = $this->completedPaymentFor($subA);

        [$userB] = $this->makeOwnerWithCompany();

        // Cross-tenant access is denied (the 403 is rendered as a redirect away).
        $this->actingAs($userB)->get(route('billing.invoice', $foreignPayment))
            ->assertRedirect(route('dashboard'));
    }
}
