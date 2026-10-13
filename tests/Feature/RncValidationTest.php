<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Services\DgcpProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class RncValidationTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private const PROVIDERS = 'datosabiertos.dgcp.gob.do/api-dgcp/v1/proveedores*';

    private function fakeRegistry(?string $foundRnc): void
    {
        Http::fake([self::PROVIDERS => Http::response([
            'code' => 200,
            'payload' => ['content' => $foundRnc === null
                ? null
                : [['numero_documento' => $foundRnc, 'razon_social' => 'Empresa Real SRL']]],
            'totalResults' => $foundRnc === null ? null : 1,
        ])]);
    }

    public static function malformedRncs(): array
    {
        return [
            'four digits (user 29)' => ['9999'],
            'five digits (user 30)' => ['32928'],
            'too short' => ['1234567'],
            'too long' => ['1234567890'],
            'all same digit' => ['999999999'],
            'all zeroes' => ['000000000'],
            'sequential' => ['123456789'],
            'letters only' => ['ABCDEFGHI'],
        ];
    }

    #[DataProvider('malformedRncs')]
    public function test_a_malformed_rnc_is_refused(string $rnc): void
    {
        $this->fakeRegistry(null);

        [$user] = $this->makeOwnerWithCompany(
            ['status' => 'trialing', 'trial_ends_at' => now()->addDays(7), 'max_companies' => 3],
        );

        $this->actingAs($user)
            ->post(route('company-setup.store'), ['razon_social' => 'Pepito SRL', 'rnc' => $rnc])
            ->assertSessionHasErrors('rnc');

        $this->assertDatabaseMissing('companies', ['razon_social' => 'Pepito SRL']);
    }

    /**
     * Lengths seen across 500 live DGCP registrations: 9 for a company RNC,
     * 11 for a cédula. Both must pass, punctuation and all.
     */
    public static function wellFormedRncs(): array
    {
        return [
            'nine digits' => ['130514331'],
            'nine with punctuation' => ['1-30-51433-1'],
            'eleven digits (cédula)' => ['00101413201'],
            // Fails the mod-11 check digit yet is genuinely registered — one of
            // the 16 in 488 that made the checksum unusable as a gate.
            'real but bad check digit' => ['101710129'],
        ];
    }

    #[DataProvider('wellFormedRncs')]
    public function test_a_well_formed_rnc_is_accepted(string $rnc): void
    {
        $this->fakeRegistry(preg_replace('/\D+/', '', $rnc));

        [$user] = $this->makeOwnerWithCompany(
            ['status' => 'trialing', 'trial_ends_at' => now()->addDays(7), 'max_companies' => 3],
        );

        $this->actingAs($user)
            ->post(route('company-setup.store'), ['razon_social' => 'Empresa Real SRL', 'rnc' => $rnc])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('companies', ['razon_social' => 'Empresa Real SRL']);
    }

    // ── The registry is a flag, never a gate ──────────────────────────

    public function test_an_unlisted_rnc_still_registers_but_is_flagged(): void
    {
        // A business incorporated last week: well formed, not yet in the
        // registry, which can lag by months. It must get in.
        $this->fakeRegistry(null);

        [$user] = $this->makeOwnerWithCompany(
            ['status' => 'trialing', 'trial_ends_at' => now()->addDays(7), 'max_companies' => 3],
        );

        $this->actingAs($user)
            ->post(route('company-setup.store'), ['razon_social' => 'Nueva Empresa SRL', 'rnc' => '130514331'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('companies', [
            'razon_social' => 'Nueva Empresa SRL',
            'rnc_verified_at' => null,
        ]);
    }

    public function test_a_listed_rnc_is_marked_verified(): void
    {
        $this->fakeRegistry('130514331');

        [$user] = $this->makeOwnerWithCompany(
            ['status' => 'trialing', 'trial_ends_at' => now()->addDays(7), 'max_companies' => 3],
        );

        $this->actingAs($user)
            ->post(route('company-setup.store'), ['razon_social' => 'Empresa Real SRL', 'rnc' => '130514331']);

        $company = Company::withoutGlobalScopes()
            ->where('razon_social', 'Empresa Real SRL')->first();

        $this->assertNotNull($company->rnc_verified_at);
    }

    public function test_a_registry_outage_does_not_block_signup(): void
    {
        Http::fake([self::PROVIDERS => fn () => throw new ConnectionException('timed out')]);

        [$user] = $this->makeOwnerWithCompany(
            ['status' => 'trialing', 'trial_ends_at' => now()->addDays(7), 'max_companies' => 3],
        );

        $this->actingAs($user)
            ->post(route('company-setup.store'), ['razon_social' => 'Empresa Real SRL', 'rnc' => '130514331'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('companies', ['razon_social' => 'Empresa Real SRL']);
    }

    // ── The service's three-valued answer ─────────────────────────────

    public function test_a_listed_rnc_reports_true_ignoring_punctuation(): void
    {
        $this->fakeRegistry('130514331');

        $this->assertTrue(app(DgcpProviderService::class)->isRegistered('1-30-51433-1'));
    }

    public function test_an_absent_rnc_reports_false(): void
    {
        $this->fakeRegistry(null);

        $this->assertFalse(app(DgcpProviderService::class)->isRegistered('130514331'));
    }

    public function test_a_malformed_rnc_is_unknown_not_absent(): void
    {
        $this->fakeRegistry(null);

        // Worth no query, so the answer is "don't know" rather than "not there".
        $this->assertNull(app(DgcpProviderService::class)->isRegistered('9999'));
        Http::assertNothingSent();
    }

    public function test_a_registry_error_is_unknown_not_absent(): void
    {
        Http::fake([self::PROVIDERS => Http::response('', 503)]);

        // Must not read as "not registered", or an outage would flag every
        // company that signed up during it.
        $this->assertNull(app(DgcpProviderService::class)->isRegistered('130514331'));
    }

    public function test_a_row_for_a_different_rnc_is_not_treated_as_a_match(): void
    {
        // Passing an unsupported filter returns the whole 128k-row list, whose
        // first row would otherwise look like a match for anything.
        $this->fakeRegistry('101870087');

        $this->assertFalse(app(DgcpProviderService::class)->isRegistered('130514331'));
    }

    // ── Every door, not just the setup form ───────────────────────────

    public function test_the_add_company_path_also_refuses_a_bad_rnc(): void
    {
        $this->fakeRegistry(null);

        [$user] = $this->makeOwnerWithCompany(['status' => 'active', 'max_companies' => 3]);

        $this->actingAs($user)
            ->post(route('companies.store'), ['razon_social' => 'Pepito Dos SRL', 'rnc' => '9999'])
            ->assertSessionHasErrors('rnc');

        $this->assertDatabaseMissing('companies', ['razon_social' => 'Pepito Dos SRL']);
    }

    public function test_the_profile_edit_path_cannot_replace_a_good_rnc_with_junk(): void
    {
        $this->fakeRegistry(null);

        [$user, , $company] = $this->makeOwnerWithCompany(['status' => 'active'], ['rnc' => '130514331']);

        $this->actingAs($user)
            ->post(route('empresa.update'), ['razon_social' => $company->razon_social, 'rnc' => '32928'])
            ->assertSessionHasErrors('rnc');

        $this->assertDatabaseHas('companies', ['id' => $company->id, 'rnc' => '130514331']);
    }

    public function test_the_profile_edit_path_still_allows_clearing_the_rnc(): void
    {
        $this->fakeRegistry(null);

        [$user, , $company] = $this->makeOwnerWithCompany(['status' => 'active'], ['rnc' => '130514331']);

        $this->actingAs($user)
            ->post(route('empresa.update'), ['razon_social' => $company->razon_social, 'rnc' => null])
            ->assertSessionHasNoErrors();
    }
}
