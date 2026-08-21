<?php

namespace Tests\Feature;

use App\Mail\SubscriptionEndedMail;
use App\Mail\TrialEndingMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class BillingLifecycleEmailTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    public function test_reconcile_suspends_past_due_after_grace_and_notifies(): void
    {
        Mail::fake();

        [, $sub] = $this->makeOwnerWithCompany([
            'status' => 'past_due',
            'grace_ends_at' => now()->subDay(),
        ]);

        $this->artisan('subscriptions:reconcile')->assertSuccessful();

        $this->assertSame('suspended', $sub->fresh()->status);
        Mail::assertSent(SubscriptionEndedMail::class);
    }

    public function test_trial_ending_email_sends_once_and_is_idempotent(): void
    {
        Mail::fake();

        [, $sub] = $this->makeOwnerWithCompany([
            'status' => 'trialing',
            'trial_ends_at' => now()->addDay(),
        ]);

        $this->artisan('secp:send-trial-ending')->assertSuccessful();
        Mail::assertSent(TrialEndingMail::class, 1);
        $this->assertNotNull($sub->fresh()->trial_ending_notified_at);

        // Running again must not re-send.
        $this->artisan('secp:send-trial-ending')->assertSuccessful();
        Mail::assertSent(TrialEndingMail::class, 1);
    }
}
