<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Company;
use App\Models\Rubro;
use App\Models\TrialClaim;
use App\Services\BidMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class LapsedCompanyLifecycleTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    // ── Entitlement gate on matching ──────────────────────────────────

    public function test_entitled_ids_covers_active_live_trial_and_grace(): void
    {
        [, , $active] = $this->makeOwnerWithCompany(['status' => 'active']);
        [, , $trialing] = $this->makeOwnerWithCompany([
            'status' => 'trialing', 'trial_ends_at' => now()->addDays(3),
        ]);
        [, , $inGrace] = $this->makeOwnerWithCompany([
            'status' => 'past_due', 'grace_ends_at' => now()->addDays(2),
        ]);
        [, , $lapsedTrial] = $this->makeOwnerWithCompany([
            'status' => 'trialing', 'trial_ends_at' => now()->subDays(10),
        ]);
        [, , $cancelled] = $this->makeOwnerWithCompany([
            'status' => 'cancelled', 'cancelled_at' => now()->subDays(10),
        ]);

        $ids = Company::entitledIds()->all();

        $this->assertContains($active->id, $ids);
        $this->assertContains($trialing->id, $ids);
        $this->assertContains($inGrace->id, $ids);
        $this->assertNotContains($lapsedTrial->id, $ids);
        $this->assertNotContains($cancelled->id, $ids);
    }

    public function test_matching_ignores_rubros_of_lapsed_companies(): void
    {
        [, , $paying] = $this->makeOwnerWithCompany(['status' => 'active']);
        [, , $lapsed] = $this->makeOwnerWithCompany([
            'status' => 'trialing', 'trial_ends_at' => now()->subDays(30),
        ]);

        foreach ([$paying, $lapsed] as $company) {
            Rubro::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'code' => '43211500',
                'name' => 'Computadores',
                'level' => 'clase',
                'active' => true,
            ]);
        }

        $matcher = app(BidMatchingService::class);
        $map = $matcher->aggregateRubros();

        $this->assertArrayHasKey('43211500', $map);
        $this->assertContains($paying->id, $map['43211500']['company_ids']);
        $this->assertNotContains($lapsed->id, $map['43211500']['company_ids'],
            'A lapsed company must not keep the poller querying DGCP for its rubros.');

        $bid = Bid::create([
            'process_code' => 'TEST-LAPSED-001',
            'title' => 'Compra de computadores',
            'institution' => 'Institución de prueba',
            'status' => 'Publicado',
        ]);

        $matcher->fanOutToCompanies($bid, [['code' => '43211500', 'name' => 'Computadores']], $map);

        $matched = DB::table('company_bid')->where('bid_id', $bid->id)->pluck('company_id')->all();

        $this->assertContains($paying->id, $matched);
        $this->assertNotContains($lapsed->id, $matched, 'A lapsed company must not receive new matches.');
    }

    // ── One trial per RNC ─────────────────────────────────────────────

    public function test_trial_claim_normalizes_rnc_punctuation(): void
    {
        TrialClaim::claim('1-30-12345-6', 'Empresa SRL', null);

        $this->assertTrue(TrialClaim::hasClaimed('130123456'));
        $this->assertTrue(TrialClaim::hasClaimed('1.30.12345.6'));
        $this->assertFalse(TrialClaim::hasClaimed('999999999'));
        $this->assertFalse(TrialClaim::hasClaimed(null));
    }

    public function test_claim_does_not_block_the_user_who_made_it(): void
    {
        [$owner] = $this->makeOwnerWithCompany();

        TrialClaim::claim('130123456', 'Empresa SRL', $owner->id);

        $this->assertFalse(TrialClaim::hasClaimed('130123456', $owner->id));
        $this->assertTrue(TrialClaim::hasClaimed('130123456', $owner->id + 1));
    }

    public function test_second_trial_for_a_claimed_rnc_is_blocked(): void
    {
        TrialClaim::claim('130999888', 'Vieja Empresa SRL', null);

        [$user] = $this->makeOwnerWithCompany(
            ['status' => 'trialing', 'trial_ends_at' => now()->addDays(7), 'max_companies' => 3],
        );

        $response = $this->actingAs($user)->post(route('company-setup.store'), [
            'razon_social' => 'Vieja Empresa SRL',
            'rnc' => '1-30-99988-8',
        ]);

        $response->assertSessionHas('warning');
        $this->assertDatabaseMissing('companies', ['razon_social' => 'Vieja Empresa SRL']);
    }

    public function test_a_paying_customer_may_use_a_claimed_rnc(): void
    {
        TrialClaim::claim('130777666', 'Empresa Pagada SRL', null);

        [$user] = $this->makeOwnerWithCompany(['status' => 'active', 'max_companies' => 3]);

        $this->actingAs($user)->post(route('company-setup.store'), [
            'razon_social' => 'Empresa Pagada SRL',
            'rnc' => '1-30-77766-6',
        ]);

        $this->assertDatabaseHas('companies', ['razon_social' => 'Empresa Pagada SRL']);
    }

    public function test_creating_a_company_on_trial_records_the_claim(): void
    {
        [$user] = $this->makeOwnerWithCompany(
            ['status' => 'trialing', 'trial_ends_at' => now()->addDays(7), 'max_companies' => 3],
        );

        $this->actingAs($user)->post(route('company-setup.store'), [
            'razon_social' => 'Nueva Empresa SRL',
            'rnc' => '1-30-55544-3',
        ]);

        $this->assertDatabaseHas('trial_claims', ['rnc' => '130555443']);
    }

    // ── Purge ─────────────────────────────────────────────────────────

    public function test_purge_removes_lapsed_company_data_and_leaves_billing(): void
    {
        [$user, $subscription, $company] = $this->makeOwnerWithCompany([
            'status' => 'trialing', 'trial_ends_at' => now()->subDays(200),
        ]);

        Rubro::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'code' => '43211500',
            'name' => 'Computadores',
            'level' => 'clase',
            'active' => true,
        ]);

        $this->artisan('companies:purge-lapsed', ['--days' => 90])->assertSuccessful();

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
        $this->assertDatabaseMissing('rubros', ['company_id' => $company->id]);
        $this->assertDatabaseMissing('company_user', ['company_id' => $company->id]);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id]);
        $this->assertDatabaseHas('trial_claims', ['rnc' => TrialClaim::normalize($company->rnc)]);
        $this->assertNotNull(TrialClaim::first()->purged_at);
    }

    public function test_purge_spares_paying_and_recently_lapsed_companies(): void
    {
        [, , $paying] = $this->makeOwnerWithCompany(['status' => 'active']);
        [, , $recent] = $this->makeOwnerWithCompany([
            'status' => 'cancelled', 'cancelled_at' => now()->subDays(5),
        ]);

        $this->artisan('companies:purge-lapsed', ['--days' => 90])->assertSuccessful();

        $this->assertDatabaseHas('companies', ['id' => $paying->id]);
        $this->assertDatabaseHas('companies', ['id' => $recent->id]);
    }

    public function test_dry_run_deletes_nothing(): void
    {
        [, , $company] = $this->makeOwnerWithCompany([
            'status' => 'cancelled', 'cancelled_at' => now()->subDays(200),
        ]);

        $this->artisan('companies:purge-lapsed', ['--days' => 90, '--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('companies', ['id' => $company->id]);
        $this->assertDatabaseCount('trial_claims', 0);
    }
}
