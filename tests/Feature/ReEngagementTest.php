<?php

namespace Tests\Feature;

use App\Mail\ReEngagementMail;
use App\Mail\SetupNudgeMail;
use App\Models\Bid;
use App\Models\CompanyBid;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class ReEngagementTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private function quietFor($user, int $days): void
    {
        $user->forceFill(['last_sign_in_at' => now()->subDays($days)])->save();
    }

    private function matchBid($company, int $closesInDays = 10): Bid
    {
        $bid = Bid::create([
            'process_code' => 'RE-'.uniqid(),
            'title' => 'Construcción de aulas',
            'buyer_name' => 'MINERD',
            'amount_estimated' => 1000000,
            'currency' => 'DOP',
            'tender_deadline' => now()->addDays($closesInDays),
        ]);
        CompanyBid::create([
            'company_id' => $company->id,
            'bid_id' => $bid->id,
            'first_matched_at' => now()->subDays(2),
            'notified_at' => now()->subDays(2),
        ]);

        return $bid;
    }

    public function test_quiet_trial_user_with_matches_gets_the_missed_bids_email(): void
    {
        Mail::fake();
        [$user, , $company] = $this->makeOwnerWithCompany(['status' => 'trialing', 'trial_ends_at' => now()->addDays(5)]);
        $this->quietFor($user, 20);
        $this->matchBid($company);

        $this->artisan('secp:send-reengagement')->assertSuccessful();

        Mail::assertSent(ReEngagementMail::class);
        Mail::assertNotSent(SetupNudgeMail::class);
    }

    public function test_quiet_trial_user_without_a_company_gets_the_setup_nudge(): void
    {
        Mail::fake();
        $user = User::create(['name' => 'Sin Empresa', 'email' => 'sinempresa@test.do', 'password' => bcrypt('x')]);
        $this->quietFor($user, 20);
        Subscription::create([
            'user_id' => $user->id, 'plan' => 'basic', 'status' => 'trialing',
            'max_companies' => 1, 'max_users' => 2, 'monthly_amount' => 0,
            'trial_ends_at' => now()->addDays(5),
        ]);

        $this->artisan('secp:send-reengagement')->assertSuccessful();

        Mail::assertSent(SetupNudgeMail::class);
        Mail::assertNotSent(ReEngagementMail::class);
    }

    public function test_a_company_with_no_missed_matches_is_left_alone(): void
    {
        Mail::fake();
        [$user] = $this->makeOwnerWithCompany();
        $this->quietFor($user, 20);

        $this->artisan('secp:send-reengagement')->assertSuccessful();

        Mail::assertNotSent(ReEngagementMail::class);
        Mail::assertNotSent(SetupNudgeMail::class);
    }

    public function test_recently_active_users_are_not_contacted(): void
    {
        Mail::fake();
        [$user, , $company] = $this->makeOwnerWithCompany();
        $this->quietFor($user, 1);
        $this->matchBid($company);

        $this->artisan('secp:send-reengagement')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_cooldown_blocks_a_resend_but_force_allows_a_backfill(): void
    {
        Mail::fake();
        [$user, , $company] = $this->makeOwnerWithCompany();
        $this->quietFor($user, 20);
        $this->matchBid($company);

        $this->artisan('secp:send-reengagement')->assertSuccessful();
        Mail::assertSent(ReEngagementMail::class, 1);

        $this->artisan('secp:send-reengagement')->assertSuccessful();
        Mail::assertSent(ReEngagementMail::class, 1);

        $this->artisan('secp:send-reengagement --force')->assertSuccessful();
        Mail::assertSent(ReEngagementMail::class, 2);
    }

    public function test_subject_carries_the_first_name_and_a_real_deadline(): void
    {
        [$user, , $company] = $this->makeOwnerWithCompany();
        $user->forceFill(['name' => 'Frederick Lopez'])->save();
        $bid = $this->matchBid($company, closesInDays: 3);

        $subject = (new ReEngagementMail($user, 20, collect([$bid])))->envelope()->subject;

        $this->assertStringContainsString('Frederick,', $subject);
        $this->assertStringContainsString('1 licitación', $subject);
        $this->assertStringContainsString('cierra en 3 días', $subject);
    }

    public function test_user_who_never_signed_in_but_has_a_company_does_not_crash(): void
    {
        Mail::fake();
        [$user, , $company] = $this->makeOwnerWithCompany();
        // Registered, set up a company, then never returned: last_sign_in_at is null.
        $user->forceFill(['last_sign_in_at' => null, 'created_at' => now()->subDays(40)])->save();
        $this->matchBid($company);

        $this->artisan('secp:send-reengagement')->assertSuccessful();

        Mail::assertSent(ReEngagementMail::class);
    }

    public function test_email_reports_the_true_match_count_not_the_display_cap(): void
    {
        [$user, , $company] = $this->makeOwnerWithCompany();
        $user->forceFill(['name' => 'Frederick Lopez'])->save();
        $shown = collect([$this->matchBid($company, closesInDays: 4)]);

        $mail = new ReEngagementMail($user, 122, $shown, total: 213);

        $this->assertStringContainsString('213 licitaciones', $mail->envelope()->subject);
        $this->assertStringContainsString('213', $mail->render());
    }

    public function test_disposable_addresses_are_never_emailed(): void
    {
        Mail::fake();
        [$user, , $company] = $this->makeOwnerWithCompany();
        $user->forceFill(['email' => 'mjyf5yflyp@lnovic.com', 'last_sign_in_at' => now()->subDays(20)])->save();
        $this->matchBid($company);

        $this->artisan('secp:send-reengagement')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertNull($user->fresh()->reengagement_sent_at);
    }
}
