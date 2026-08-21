<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class SubscriptionAccessTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    public function test_active_owner_reaches_the_dashboard(): void
    {
        [$user] = $this->makeOwnerWithCompany();

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_past_due_within_grace_still_reaches_the_app(): void
    {
        // Regression: a single failed payment must not lock a paying customer out.
        [$user] = $this->makeOwnerWithCompany([
            'status' => 'past_due',
            'grace_ends_at' => now()->addDays(5),
        ]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_past_due_after_grace_is_walled_to_billing(): void
    {
        [$user] = $this->makeOwnerWithCompany([
            'status' => 'past_due',
            'grace_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('billing.index'));
    }

    public function test_unverified_user_is_sent_to_email_verification(): void
    {
        [$user] = $this->makeOwnerWithCompany(verified: false);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(302);
        $this->assertStringContainsString('verificar', $response->headers->get('Location'));
    }
}
