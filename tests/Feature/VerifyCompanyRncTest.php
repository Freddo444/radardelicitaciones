<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class VerifyCompanyRncTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private const PROVIDERS = 'datosabiertos.dgcp.gob.do/api-dgcp/v1/proveedores*';

    /** Answer as the registry does: found only for the numbers listed. */
    private function fakeRegistryKnowing(array $known): void
    {
        Http::fake([self::PROVIDERS => function ($request) use ($known) {
            $asked = (string) ($request->data()['numero_documento'] ?? '');

            return Http::response([
                'payload' => ['content' => in_array($asked, $known, true)
                    ? [['numero_documento' => $asked, 'razon_social' => 'Empresa SRL']]
                    : null],
            ]);
        }]);
    }

    public function test_it_flags_only_the_companies_the_registry_knows(): void
    {
        [, , $listed] = $this->makeOwnerWithCompany([], ['rnc' => '130514331']);
        [, , $unlisted] = $this->makeOwnerWithCompany([], ['rnc' => '132978961']);
        [, , $malformed] = $this->makeOwnerWithCompany([], ['rnc' => '9999']);

        $this->fakeRegistryKnowing(['130514331']);

        $this->artisan('companies:verify-rnc')->assertSuccessful();

        $this->assertNotNull($listed->fresh()->rnc_verified_at);
        $this->assertNull($unlisted->fresh()->rnc_verified_at,
            'Not listed yet is not the same as not real — the registry lags.');
        $this->assertNull($malformed->fresh()->rnc_verified_at);
    }

    public function test_a_dry_run_writes_nothing(): void
    {
        [, , $company] = $this->makeOwnerWithCompany([], ['rnc' => '130514331']);

        $this->fakeRegistryKnowing(['130514331']);

        $this->artisan('companies:verify-rnc', ['--dry-run' => true])->assertSuccessful();

        $this->assertNull($company->fresh()->rnc_verified_at);
    }

    public function test_an_outage_never_clears_an_existing_flag(): void
    {
        [, , $company] = $this->makeOwnerWithCompany([], ['rnc' => '130514331']);
        $company->forceFill(['rnc_verified_at' => now()->subMonth()])->save();
        $verifiedAt = $company->fresh()->rnc_verified_at;

        Http::fake([self::PROVIDERS => Http::response('', 503)]);

        $this->artisan('companies:verify-rnc', ['--recheck' => true])->assertSuccessful();

        $this->assertEquals($verifiedAt, $company->fresh()->rnc_verified_at,
            'A transient failure must not un-verify a company.');
    }

    public function test_already_verified_companies_are_skipped_without_recheck(): void
    {
        [, , $company] = $this->makeOwnerWithCompany([], ['rnc' => '130514331']);
        $company->forceFill(['rnc_verified_at' => now()->subMonth()])->save();

        $this->fakeRegistryKnowing(['130514331']);

        $this->artisan('companies:verify-rnc')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_it_reaches_companies_across_every_tenant(): void
    {
        // The command runs outside a request, so the tenant scope must be off
        // or it would only ever see one company.
        $this->makeOwnerWithCompany([], ['rnc' => '130514331']);
        $this->makeOwnerWithCompany([], ['rnc' => '101870087']);

        $this->fakeRegistryKnowing(['130514331', '101870087']);

        $this->artisan('companies:verify-rnc')->assertSuccessful();

        $this->assertSame(0, Company::withoutGlobalScopes()->whereNull('rnc_verified_at')->count());
    }

    // ── The /proveedores endpoint is not always answerable ────────────

    public function test_an_rpe_with_rubros_verifies_even_when_proveedores_errors(): void
    {
        // Grupo Alzare's real shape: /proveedores answers HTTP 500 for its
        // record on every parameter, while /proveedores/rubro returns it fine.
        Http::fake([
            'datosabiertos.dgcp.gob.do/api-dgcp/v1/proveedores/rubro*' => Http::response([
                'payload' => ['content' => [[
                    'rpe' => 135240,
                    'razon_social' => 'Grupo Alzare, SRL',
                    'familia_unspsc' => '43210000',
                ]]],
                'totalResults' => 13,
            ]),
            'datosabiertos.dgcp.gob.do/api-dgcp/v1/proveedores*' => Http::response([
                'code' => 500,
                'hasError' => true,
                'payload' => ['content' => null, 'message' => 'Ha ocurrido un error al completar la consulta...'],
            ], 500),
        ]);

        [, , $company] = $this->makeOwnerWithCompany([], ['rnc' => '133649871', 'rpe_numero' => 135240]);

        $this->artisan('companies:verify-rnc')->assertSuccessful();

        $this->assertNotNull($company->fresh()->rnc_verified_at,
            'A supplier with declared rubros is registered, whatever /proveedores says.');
    }

    public function test_an_rpe_with_no_rubros_does_not_by_itself_verify(): void
    {
        // VOCASER's 647726 and pepito srl's 374044 both answer 200 with nothing.
        Http::fake([
            'datosabiertos.dgcp.gob.do/api-dgcp/v1/proveedores/rubro*' => Http::response([
                'payload' => ['content' => null],
            ]),
            'datosabiertos.dgcp.gob.do/api-dgcp/v1/proveedores*' => Http::response([
                'payload' => ['content' => null],
            ]),
        ]);

        [, , $company] = $this->makeOwnerWithCompany([], ['rnc' => '130107505', 'rpe_numero' => 647726]);

        $this->artisan('companies:verify-rnc')->assertSuccessful();

        $this->assertNull($company->fresh()->rnc_verified_at);
    }

    public function test_the_rnc_lookup_still_works_without_an_rpe(): void
    {
        $this->fakeRegistryKnowing(['133697718']);

        [, , $company] = $this->makeOwnerWithCompany([], ['rnc' => '133697718', 'rpe_numero' => null]);

        $this->artisan('companies:verify-rnc')->assertSuccessful();

        $this->assertNotNull($company->fresh()->rnc_verified_at);
    }
}
