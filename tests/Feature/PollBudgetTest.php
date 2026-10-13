<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Rubro;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class PollBudgetTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    /** Give the tenant enough rubros that a budget stop leaves some behind. */
    private function seedRubros(int $count): void
    {
        [, , $company] = $this->makeOwnerWithCompany(['status' => 'active']);

        for ($i = 0; $i < $count; $i++) {
            Rubro::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'code' => (string) (40000000 + $i * 10000),
                'name' => "Rubro {$i}",
                'level' => 'familia',
                'active' => true,
                'first_polled_at' => now()->subDay(),
            ]);
        }
    }

    private function fakeEmptyApi(): void
    {
        Http::fake(['datosabiertos.dgcp.gob.do/*' => Http::response([
            'payload' => ['content' => []],
            'pages' => 1,
        ])]);
    }

    public function test_an_exhausted_budget_stops_the_sweep_and_keeps_the_cursor(): void
    {
        $this->seedRubros(4);
        $this->fakeEmptyApi();

        // Resuming at 2, so the saved cursor should stay 2 rather than reset.
        Setting::set('poll_rubro_cursor', '2');

        // A budget this small is already spent by the time the loop begins.
        $this->artisan('secp:poll', ['--budget' => 0.000001])
            ->expectsOutputToContain('Presupuesto')
            ->assertSuccessful();

        $this->assertSame('2', Setting::get('poll_rubro_cursor'),
            'A run that ran out of budget must not reset the cursor to the top.');
    }

    public function test_a_completed_sweep_resets_the_cursor_to_the_top(): void
    {
        $this->seedRubros(3);
        $this->fakeEmptyApi();

        Setting::set('poll_rubro_cursor', '2');

        $this->artisan('secp:poll', ['--budget' => 45])->assertSuccessful();

        $this->assertSame('0', Setting::get('poll_rubro_cursor'));
    }

    public function test_a_completed_sweep_advances_last_polled_at(): void
    {
        $this->seedRubros(3);
        $this->fakeEmptyApi();

        Setting::set('last_polled_at', '2026-10-13 11:00:00');

        $this->artisan('secp:poll', ['--budget' => 45])->assertSuccessful();

        $this->assertNotSame('2026-10-13 11:00:00', Setting::get('last_polled_at'),
            'A completed run must move the window forward.');
    }

    public function test_the_cursor_rotates_the_starting_point(): void
    {
        $this->seedRubros(4);
        $this->fakeEmptyApi();

        Setting::set('poll_rubro_cursor', '2');

        $this->artisan('secp:poll', ['--budget' => 45])
            ->expectsOutputToContain('Reanudando en el rubro 2')
            ->assertSuccessful();
    }

    public function test_an_out_of_range_cursor_does_not_break_the_run(): void
    {
        $this->seedRubros(3);
        $this->fakeEmptyApi();

        // Left over from a run when the company had more rubros than it does now.
        Setting::set('poll_rubro_cursor', '999');

        $this->artisan('secp:poll', ['--budget' => 45])->assertSuccessful();
    }

    public function test_a_dry_run_leaves_the_cursor_and_window_untouched(): void
    {
        $this->seedRubros(3);
        $this->fakeEmptyApi();

        Setting::set('last_polled_at', '2026-10-13 11:00:00');
        Setting::set('poll_rubro_cursor', '1');

        $this->artisan('secp:poll', ['--dry-run' => true, '--budget' => 45])->assertSuccessful();

        $this->assertSame('2026-10-13 11:00:00', Setting::get('last_polled_at'));
        $this->assertSame('1', Setting::get('poll_rubro_cursor'));
    }

    public function test_a_timeout_on_one_rubro_no_longer_kills_the_run(): void
    {
        $this->seedRubros(3);

        $calls = 0;
        Http::fake(['datosabiertos.dgcp.gob.do/*' => function () use (&$calls) {
            $calls++;

            // Fail the first rubro outright, both attempts.
            if ($calls <= 2) {
                throw new ConnectionException('cURL error 28: Operation timed out');
            }

            return Http::response(['payload' => ['content' => []], 'pages' => 1]);
        }]);

        Setting::set('last_polled_at', '2026-10-13 11:00:00');

        $this->artisan('secp:poll', ['--budget' => 45])->assertSuccessful();

        // The run survived and still advanced the window — the whole point.
        $this->assertNotSame('2026-10-13 11:00:00', Setting::get('last_polled_at'));
    }

    // ── Archived bids must not collide with themselves ────────────────

    public function test_an_archived_bid_is_not_treated_as_new(): void
    {
        [, , $company] = $this->makeOwnerWithCompany(['status' => 'active']);

        Rubro::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'code' => '43211500',
            'name' => 'Computadores',
            'level' => 'familia',
            'active' => true,
            'first_polled_at' => now()->subDay(),
        ]);

        // Archived by cleanup() because its deadline passed. The row still
        // occupies bids_process_code_unique while being invisible to queries.
        $bid = Bid::create([
            'process_code' => 'INFOTEP-DAF-CD-2026-1983',
            'title' => 'Compra de computadores',
            'status' => 'Proceso adjudicado y celebrado',
        ]);
        $bid->delete();
        $this->assertSoftDeleted('bids', ['id' => $bid->id]);

        // The DGCP still lists it, so the poll matches it again.
        Http::fake(['datosabiertos.dgcp.gob.do/*' => Http::response([
            'payload' => ['content' => [[
                'codigo_proceso' => 'INFOTEP-DAF-CD-2026-1983',
                'familia_unspsc' => '43211500',
                'descripcion_articulo' => 'Computadores',
                'fecha_publicacion' => now()->subDays(2)->toDateString(),
            ]]],
            'pages' => 1,
        ])]);

        // The row count and soft-delete state look identical whether or not the
        // insert was attempted, because the insert fails. The failure itself is
        // the thing to assert on.
        $this->artisan('secp:poll', ['--budget' => 45])
            ->doesntExpectOutputToContain('Error al guardar')
            ->doesntExpectOutputToContain('Fallidos')
            ->assertSuccessful();

        $this->assertSame(1, Bid::withTrashed()
            ->where('process_code', 'INFOTEP-DAF-CD-2026-1983')->count());
        $this->assertSoftDeleted('bids', ['id' => $bid->id]);
    }

    public function test_an_archived_bid_is_not_fanned_out_to_companies(): void
    {
        [, , $company] = $this->makeOwnerWithCompany(['status' => 'active']);

        Rubro::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'code' => '43211500',
            'name' => 'Computadores',
            'level' => 'familia',
            'active' => true,
            'first_polled_at' => now()->subDay(),
        ]);

        $bid = Bid::create([
            'process_code' => 'ARCHIVED-2026-0001',
            'title' => 'Compra vieja',
            'status' => 'Proceso desierto',
        ]);
        $bid->delete();

        Http::fake(['datosabiertos.dgcp.gob.do/*' => Http::response([
            'payload' => ['content' => [[
                'codigo_proceso' => 'ARCHIVED-2026-0001',
                'familia_unspsc' => '43211500',
                'descripcion_articulo' => 'Computadores',
                'fecha_publicacion' => now()->subDays(2)->toDateString(),
            ]]],
            'pages' => 1,
        ])]);

        $this->artisan('secp:poll', ['--budget' => 45])->assertSuccessful();

        $this->assertDatabaseMissing('company_bid', ['bid_id' => $bid->id]);
    }
}
