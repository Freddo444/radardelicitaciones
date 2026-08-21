<?php

namespace Tests\Unit;

use App\Models\Subscription;
use Tests\TestCase;

class SubscriptionStatusTest extends TestCase
{
    public function test_active_subscription_is_active(): void
    {
        $this->assertTrue((new Subscription(['status' => 'active']))->isActive());
    }

    public function test_trialing_within_window_is_active_but_expired_trial_is_not(): void
    {
        $live = new Subscription(['status' => 'trialing']);
        $live->trial_ends_at = now()->addDays(3);
        $this->assertTrue($live->isActive());

        $expired = new Subscription(['status' => 'trialing']);
        $expired->trial_ends_at = now()->subDay();
        $this->assertFalse($expired->isActive());
        $this->assertTrue($expired->trialExpired());
    }

    public function test_past_due_keeps_access_during_grace_window(): void
    {
        $inGrace = new Subscription(['status' => 'past_due']);
        $inGrace->grace_ends_at = now()->addDays(3);

        $this->assertTrue($inGrace->inDunningGrace());
        $this->assertTrue($inGrace->isActive(), 'A past_due subscription within grace must keep access');
    }

    public function test_past_due_loses_access_after_grace_and_without_grace(): void
    {
        $lapsed = new Subscription(['status' => 'past_due']);
        $lapsed->grace_ends_at = now()->subDay();
        $this->assertFalse($lapsed->isActive(), 'A past_due subscription past its grace window must be walled');

        $noGrace = new Subscription(['status' => 'past_due']);
        $this->assertFalse($noGrace->isActive());
    }

    public function test_cancelled_and_suspended_are_not_active(): void
    {
        $this->assertFalse((new Subscription(['status' => 'cancelled']))->isActive());
        $this->assertFalse((new Subscription(['status' => 'suspended']))->isActive());
    }
}
