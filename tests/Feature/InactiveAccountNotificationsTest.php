<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class InactiveAccountNotificationsTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    public function test_active_company_is_entitled_to_notifications(): void
    {
        [, , $company] = $this->makeOwnerWithCompany();

        $this->assertTrue($company->hasActiveSubscription());
    }

    public function test_cancelled_and_suspended_companies_are_not_entitled(): void
    {
        [, , $cancelled] = $this->makeOwnerWithCompany(['status' => 'cancelled']);
        $this->assertFalse($cancelled->hasActiveSubscription());

        [, , $suspended] = $this->makeOwnerWithCompany(['status' => 'suspended']);
        $this->assertFalse($suspended->hasActiveSubscription());
    }

    public function test_past_due_within_grace_is_still_entitled(): void
    {
        [, , $company] = $this->makeOwnerWithCompany([
            'status' => 'past_due',
            'grace_ends_at' => now()->addDays(3),
        ]);

        $this->assertTrue($company->hasActiveSubscription());
    }

    public function test_digest_is_not_sent_to_a_cancelled_account(): void
    {
        Mail::fake();

        [, , $company] = $this->makeOwnerWithCompany(['status' => 'cancelled']);
        Setting::set('digest_enabled', '1', $company->id);
        Setting::set('digest_frequency', 'hourly', $company->id);
        Setting::set('notification_email', 'lapsed@test.do', $company->id);

        $this->artisan('secp:send-digest')->assertSuccessful();

        Mail::assertNothingSent();
    }
}
