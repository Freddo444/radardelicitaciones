<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\Billing\AzulCheckoutBuilder;
use App\Services\Billing\BpdRateClient;
use App\Services\Billing\UsdDopExchange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UsdDopRateTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://bpd.test/consultatasa';

    // Catalogue root, NOT under /consultatasa — the live sandbox 404s otherwise.
    private const TOKEN = 'https://bpd.test/bpd/Authentication/oauth2/token';

    private const TASA = 'https://bpd.test/consultatasa/consultaTasa';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'services.bpd.base_url' => self::BASE,
            'services.bpd.client_id' => 'test-client',
            'services.bpd.client_secret' => 'test-secret',
            'services.bpd.rate_side' => 'venta',
            'services.bpd.rate_min' => 40,
            'services.bpd.rate_max' => 120,
            'services.azul.usd_dop_rate' => 62,
        ]);
    }

    private function fakeBank(mixed $tasaBody, int $tasaStatus = 200): void
    {
        Http::fake([
            self::TOKEN => Http::response(['access_token' => 'tok-abc', 'expires_in' => 3600]),
            self::TASA => Http::response($tasaBody, $tasaStatus),
        ]);
    }

    private function monedas(mixed $usdVenta, mixed $usdCompra = 61.0): array
    {
        return ['monedas' => ['moneda' => [
            ['descripcion' => 'USD', 'compra' => $usdCompra, 'venta' => $usdVenta],
            ['descripcion' => 'EUR', 'compra' => 66.0, 'venta' => 68.5],
        ]]];
    }

    // ── Reading the bank ──────────────────────────────────────────────

    public function test_it_stores_the_usd_sell_rate(): void
    {
        $this->fakeBank($this->monedas(63.25));

        $this->artisan('billing:refresh-usd-rate')->assertSuccessful();

        $this->assertSame(63.25, UsdDopExchange::autoRate());
        $this->assertSame(63.25, UsdDopExchange::rate());
        $this->assertSame('auto', UsdDopExchange::source());
    }

    public function test_it_uses_venta_not_compra(): void
    {
        // compra would quietly discount every sale by the spread.
        $this->fakeBank($this->monedas(63.25, 61.0));

        $this->artisan('billing:refresh-usd-rate')->assertSuccessful();

        $this->assertSame(63.25, UsdDopExchange::autoRate());
    }

    /**
     * The spec declares compra/venta as integers but shows 56.95, and the
     * bank's reference is login-gated, so both encodings must survive.
     */
    public static function rateEncodings(): array
    {
        return [
            'json float' => [63.25, 63.25],
            'json int' => [63, 63.0],
            'numeric string' => ['63.25', 63.25],
            'string with comma' => ['1,063.25', 1063.25],
        ];
    }

    #[DataProvider('rateEncodings')]
    public function test_it_parses_either_encoding(mixed $given, float $expected): void
    {
        $this->fakeBank($this->monedas($given));

        $rates = app(BpdRateClient::class)->rates();

        $this->assertSame($expected, $rates['USD']['venta']);
    }

    // ── The guard that protects the card ──────────────────────────────

    public static function implausibleRates(): array
    {
        return [
            'decimal shifted down' => [6.325],
            'decimal shifted up' => [632.5],
            'zero' => [0],
            'negative' => [-63.25],
        ];
    }

    #[DataProvider('implausibleRates')]
    public function test_an_implausible_rate_is_refused_and_not_stored(mixed $rate): void
    {
        Setting::set(UsdDopExchange::AUTO_SETTING_KEY, '62.5', null);

        $this->fakeBank($this->monedas($rate));

        $this->artisan('billing:refresh-usd-rate')->assertFailed();

        // The previous good rate must survive a bad reading untouched.
        $this->assertSame(62.5, UsdDopExchange::autoRate());
    }

    public function test_a_rate_at_the_band_edge_is_accepted(): void
    {
        $this->fakeBank($this->monedas(120.0));

        $this->artisan('billing:refresh-usd-rate')->assertSuccessful();

        $this->assertSame(120.0, UsdDopExchange::autoRate());
    }

    // ── Precedence ────────────────────────────────────────────────────

    /**
     * The regression that would have made this whole feature a no-op. A
     * migration seeds billing_usd_dop_rate and production had been edited to
     * 59.9, so if a stored manual value outranked the bank, automation would
     * fetch a rate, report success, and change nothing about what customers
     * were charged.
     */
    public function test_a_leftover_manual_value_does_not_silently_beat_the_bank(): void
    {
        Setting::set(UsdDopExchange::SETTING_KEY, '59.9', null);
        $this->fakeBank($this->monedas(63.25));

        $this->artisan('billing:refresh-usd-rate')->assertSuccessful();

        $this->assertSame(63.25, UsdDopExchange::rate(),
            'The bank rate must reach the charge, not sit behind a seeded row.');
        $this->assertSame('auto', UsdDopExchange::source());
    }

    public function test_manual_mode_pauses_automation(): void
    {
        Setting::set(UsdDopExchange::MODE_SETTING_KEY, 'manual', null);
        Setting::set(UsdDopExchange::SETTING_KEY, '65', null);
        $this->fakeBank($this->monedas(63.25));

        $this->artisan('billing:refresh-usd-rate')
            ->expectsOutputToContain('MANUAL')
            ->assertSuccessful();

        $this->assertSame(65.0, UsdDopExchange::rate(), 'A deliberate pause must hold.');
        $this->assertSame(63.25, UsdDopExchange::autoRate(), 'The bank rate is still recorded.');
        $this->assertSame('manual', UsdDopExchange::source());
    }

    public function test_the_stored_manual_value_covers_the_gap_before_the_first_fetch(): void
    {
        // A migration seeds 62, so an install that has never reached the bank
        // behaves exactly as it did before this feature existed.
        Setting::set(UsdDopExchange::SETTING_KEY, '59.9', null);

        $this->assertSame(59.9, UsdDopExchange::rate());
        $this->assertSame('manual', UsdDopExchange::source());
    }

    public function test_it_falls_back_to_config_with_no_settings_at_all(): void
    {
        Setting::where('key', 'like', 'billing_usd_dop_rate%')->delete();

        $this->assertSame(62.0, UsdDopExchange::rate());
        $this->assertSame('fallback', UsdDopExchange::source());
    }

    // ── Failure never changes a price ─────────────────────────────────

    public function test_an_outage_leaves_the_stored_rate_alone(): void
    {
        Setting::set(UsdDopExchange::AUTO_SETTING_KEY, '62.5', null);

        Http::fake([
            self::TOKEN => Http::response(['access_token' => 'tok-abc', 'expires_in' => 3600]),
            self::TASA => fn () => throw new ConnectionException('timed out'),
        ]);

        $this->artisan('billing:refresh-usd-rate')->assertFailed();

        $this->assertSame(62.5, UsdDopExchange::autoRate());
        $this->assertSame(62.5, UsdDopExchange::rate());
    }

    public function test_rejected_credentials_do_not_change_the_rate(): void
    {
        Setting::set(UsdDopExchange::AUTO_SETTING_KEY, '62.5', null);

        Http::fake([
            self::TOKEN => Http::response(['error' => 'invalid_client'], 401),
            self::TASA => Http::response($this->monedas(63.25)),
        ]);

        $this->artisan('billing:refresh-usd-rate')->assertFailed();

        $this->assertSame(62.5, UsdDopExchange::autoRate());
    }

    public function test_an_unconfigured_install_fails_loudly_rather_than_guessing(): void
    {
        config(['services.bpd.client_id' => null, 'services.bpd.client_secret' => null]);

        $this->artisan('billing:refresh-usd-rate')->assertFailed();

        $this->assertNull(UsdDopExchange::autoRate());
        Http::assertNothingSent();
    }

    public function test_a_response_without_usd_is_refused(): void
    {
        $this->fakeBank(['monedas' => ['moneda' => [
            ['descripcion' => 'EUR', 'compra' => 66.0, 'venta' => 68.5],
        ]]]);

        $this->artisan('billing:refresh-usd-rate')->assertFailed();

        $this->assertNull(UsdDopExchange::autoRate());
    }

    public function test_an_empty_currency_list_is_refused(): void
    {
        $this->fakeBank(['monedas' => ['moneda' => null]]);

        $this->artisan('billing:refresh-usd-rate')->assertFailed();

        $this->assertNull(UsdDopExchange::autoRate());
    }

    // ── Auth mechanics ────────────────────────────────────────────────

    public function test_it_falls_back_to_basic_auth_when_the_body_form_is_rejected(): void
    {
        // The spec names the client_credentials flow but not where the
        // credentials go, so both are attempted.
        $tokenCalls = 0;

        Http::fake([
            self::TOKEN => function ($request) use (&$tokenCalls) {
                $tokenCalls++;

                if ($tokenCalls === 1) {
                    return Http::response(['error' => 'invalid_request'], 400);
                }

                return Http::response(['access_token' => 'tok-basic', 'expires_in' => 3600]);
            },
            self::TASA => Http::response($this->monedas(63.25)),
        ]);

        $this->artisan('billing:refresh-usd-rate')->assertSuccessful();

        $this->assertSame(2, $tokenCalls);
        $this->assertSame(63.25, UsdDopExchange::autoRate());
    }

    public function test_it_sends_the_client_id_header_and_bearer_token(): void
    {
        $this->fakeBank($this->monedas(63.25));

        app(BpdRateClient::class)->rates();

        Http::assertSent(fn ($request) => $request->url() === self::TASA
            && $request->hasHeader('X-IBM-Client-Id', 'test-client')
            && $request->hasHeader('Authorization', 'Bearer tok-abc'));
    }

    public function test_the_token_is_cached_across_calls(): void
    {
        $this->fakeBank($this->monedas(63.25));

        $client = app(BpdRateClient::class);
        $client->rates();
        $client->rates();

        $tokenRequests = collect(Http::recorded())
            ->filter(fn ($pair) => $pair[0]->url() === self::TOKEN)
            ->count();

        $this->assertSame(1, $tokenRequests, 'The token should be reused, not re-fetched.');
    }

    public function test_a_401_on_the_rate_call_clears_the_cached_token(): void
    {
        Cache::put('bpd:oauth_token', 'stale-token', 3600);

        Http::fake([
            self::TOKEN => Http::response(['access_token' => 'tok-new', 'expires_in' => 3600]),
            self::TASA => Http::response(['httpCode' => '401'], 401),
        ]);

        $this->assertNull(app(BpdRateClient::class)->rates());
        $this->assertNull(Cache::get('bpd:oauth_token'), 'A revoked token must not be reused next run.');
    }

    // ── Staleness reporting ───────────────────────────────────────────

    public function test_staleness_is_reported_but_the_rate_is_still_used(): void
    {
        Setting::set(UsdDopExchange::AUTO_SETTING_KEY, '62.5', null);
        Setting::set(UsdDopExchange::AUTO_AT_SETTING_KEY, now()->subDays(9)->toDateTimeString(), null);

        $this->assertTrue(UsdDopExchange::autoRateIsStale());
        $this->assertSame(62.5, UsdDopExchange::rate(),
            'A real rate from last week still beats a hardcoded constant.');
    }

    public function test_an_install_with_no_auto_rate_is_not_stale(): void
    {
        $this->assertFalse(UsdDopExchange::autoRateIsStale());
    }

    // ── What actually reaches the card ────────────────────────────────

    public function test_the_azul_charge_uses_the_fetched_rate(): void
    {
        $this->fakeBank($this->monedas(63.25));
        $this->artisan('billing:refresh-usd-rate')->assertSuccessful();

        $checkout = app(AzulCheckoutBuilder::class)->forChargedUsd(45.0);

        // 45 USD * 63.25 = 2846.25 DOP -> 284625 minor units.
        $this->assertSame(284625, $checkout['amount_cents']);
        // ITBIS is inclusive: 18/118 of the total.
        $this->assertSame((int) round(284625 * 18 / 118), $checkout['itbis_cents']);
    }

    public function test_a_dry_run_reports_without_storing(): void
    {
        $this->fakeBank($this->monedas(63.25));

        $this->artisan('billing:refresh-usd-rate', ['--dry-run' => true])->assertSuccessful();

        $this->assertNull(UsdDopExchange::autoRate());
    }

    // ── The admin page ────────────────────────────────────────────────

    public function test_the_admin_page_shows_the_source_and_last_reading(): void
    {
        Setting::set(UsdDopExchange::AUTO_SETTING_KEY, '63.25', null);
        Setting::set(UsdDopExchange::AUTO_AT_SETTING_KEY, now()->subHour()->toDateTimeString(), null);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin-rate@test.do',
            'password' => bcrypt('secret1234'), 'email_verified_at' => now(),
        ]);
        $admin->forceFill(['is_super_admin' => true])->save();

        $this->actingAs($admin)
            ->get(route('admin.billing-settings.edit'))
            ->assertOk()
            ->assertSee('Banco Popular')
            ->assertSee('63.2500');
    }

    public function test_an_admin_can_switch_to_manual_and_back(): void
    {
        Setting::set(UsdDopExchange::AUTO_SETTING_KEY, '63.25', null);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin-rate2@test.do',
            'password' => bcrypt('secret1234'), 'email_verified_at' => now(),
        ]);
        $admin->forceFill(['is_super_admin' => true])->save();

        $this->actingAs($admin)->patch(route('admin.billing-settings.update'), [
            'mode' => 'manual', 'usd_dop_rate' => '65',
        ])->assertRedirect();

        $this->assertTrue(UsdDopExchange::isManualMode());
        $this->assertSame(65.0, UsdDopExchange::rate());

        $this->actingAs($admin)->patch(route('admin.billing-settings.update'), [
            'mode' => 'auto', 'usd_dop_rate' => '65',
        ])->assertRedirect();

        $this->assertFalse(UsdDopExchange::isManualMode());
        $this->assertSame(63.25, UsdDopExchange::rate(), 'Switching back must hand control to the bank.');
    }

    public function test_the_token_url_sits_above_the_api_segment(): void
    {
        // Appending the token path to the full base URL yields
        // /consultatasa/bpd/Authentication/... which the sandbox answers with
        // 404 "No resources match requested URI". Pinned because the spec
        // describes this endpoint twice and inconsistently.
        $this->fakeBank($this->monedas(63.25));

        app(BpdRateClient::class)->rates();

        Http::assertSent(fn ($request) => $request->url() === self::TOKEN);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/consultatasa/bpd/Authentication'));
    }

    public function test_an_explicit_token_url_overrides_the_derivation(): void
    {
        config(['services.bpd.token_url' => 'https://bpd.test/custom/token']);

        Http::fake([
            'https://bpd.test/custom/token' => Http::response(['access_token' => 'tok-x', 'expires_in' => 3600]),
            self::TASA => Http::response($this->monedas(63.25)),
        ]);

        $this->assertSame(63.25, app(BpdRateClient::class)->rates()['USD']['venta']);
    }
}
