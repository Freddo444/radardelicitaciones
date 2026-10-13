<?php

namespace Tests\Feature;

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
}
