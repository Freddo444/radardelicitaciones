<?php

namespace Tests\Feature;

use App\Services\PortalScraperService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PortalSweepTest extends TestCase
{
    /** @var list<array{code: string, procedure: string, deadline: Carbon, status: string}> */
    private array $portal = [];

    private int $searches = 0;

    private bool $ignoreDeadlineFilter = false;

    private bool $portalDown = false;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-16 12:00:00');
        $this->fakePortal();
    }

    public function test_full_windows_are_split_until_every_open_notice_is_seen(): void
    {
        // 250 Contratación Menor notices over ten days: no single window
        // under 100, so the sweep has to split.
        for ($i = 0; $i < 250; $i++) {
            $this->notice("CM-{$i}", 'DGCP-01-ComprasMenores', now()->addMinutes(60 + $i * 57));
        }
        $this->notice('LPN-1', 'DGCP-05-LicitacionPublicaNacional', now()->addDays(40), 'Canceled');

        $sweep = app(PortalScraperService::class)->sweepOpenNotices();

        $this->assertCount(251, $sweep['notices']);
        $this->assertSame([], $sweep['failed']);
        $this->assertSame(0, $sweep['capped']);
        $this->assertSame('Canceled', $sweep['notices']['LPN-1']['portal_status'],
            'The sweep reports every status, which is how a cancellation is seen.');
    }

    public function test_the_windows_of_one_sweep_make_the_next_one_cheaper(): void
    {
        for ($i = 0; $i < 250; $i++) {
            $this->notice("CM-{$i}", 'DGCP-01-ComprasMenores', now()->addMinutes(60 + $i * 57));
        }

        $first = app(PortalScraperService::class)->sweepOpenNotices();
        $coldSearches = $this->searches;

        $this->searches = 0;
        $second = app(PortalScraperService::class)->sweepOpenNotices($first['hints']);

        $this->assertCount(250, $second['notices']);
        $this->assertLessThan($coldSearches, $this->searches,
            'Starting from the last run\'s windows must not repeat its splitting.');
        // No splitting at all: nine types in one window each, CM in exactly
        // the windows the first sweep settled on.
        $cmWindows = count($first['hints']['DGCP-01-ComprasMenores']) + 1;
        $this->assertSame(9 + $cmWindows, $this->searches);
    }

    public function test_a_portal_that_ignores_the_filter_abandons_the_type_instead_of_splitting_forever(): void
    {
        for ($i = 0; $i < 150; $i++) {
            $this->notice("CM-{$i}", 'DGCP-01-ComprasMenores', now()->addDays(2)->addMinutes($i));
        }
        $this->ignoreDeadlineFilter = true;

        $sweep = app(PortalScraperService::class)->sweepOpenNotices();

        $this->assertContains('DGCP-01-ComprasMenores', $sweep['failed']);
        $this->assertLessThan(20, $this->searches);
    }

    public function test_the_request_cap_stops_a_runaway_sweep(): void
    {
        for ($i = 0; $i < 250; $i++) {
            $this->notice("CM-{$i}", 'DGCP-01-ComprasMenores', now()->addMinutes(60 + $i * 57));
        }

        $sweep = app(PortalScraperService::class)->sweepOpenNotices([], maxRequests: 3);

        $this->assertNotEmpty($sweep['failed']);
        $this->assertSame(3, $this->searches);
    }

    public function test_a_failing_portal_stops_the_sweep_and_keeps_the_last_windows(): void
    {
        for ($i = 0; $i < 250; $i++) {
            $this->notice("CM-{$i}", 'DGCP-01-ComprasMenores', now()->addMinutes(60 + $i * 57));
        }
        $hints = app(PortalScraperService::class)->sweepOpenNotices()['hints'];

        $this->searches = 0;
        $this->portalDown = true;
        $sweep = app(PortalScraperService::class)->sweepOpenNotices($hints);

        $this->assertCount(10, $sweep['failed'], 'Every type is reported as not swept.');
        $this->assertLessThanOrEqual(4, $this->searches, 'Two failed searches, each retried once, then stop.');
        $this->assertSame($hints, $sweep['hints'], 'An outage must not throw away the windows that work.');
    }

    public function test_a_single_notice_is_found_by_reference(): void
    {
        $this->notice('MIMARENA-CCC-LPN-2026-0004', 'DGCP-05-LicitacionPublicaNacional', now()->subDay(), 'Awarded');

        $row = app(PortalScraperService::class)->findByReference('MIMARENA-CCC-LPN-2026-0004');

        $this->assertSame('Awarded', $row['portal_status']);
        $this->assertNull(app(PortalScraperService::class)->findByReference('NOPE-2026-0001'));
    }

    // ── A portal that honours its filters the way the real one does ──

    private function notice(string $code, string $procedure, Carbon $deadline, string $status = 'Published'): void
    {
        $this->portal[] = ['code' => $code, 'procedure' => $procedure, 'deadline' => $deadline->copy(), 'status' => $status];
    }

    private function fakePortal(): void
    {
        Http::fake(['comunidad.comprasdominicana.gob.do/*' => function (Request $request) {
            if (str_contains($request->url(), '/Index')) {
                return Http::response('<html>AdvancedSearchAjax?mkey=abc123_0</html>');
            }

            $this->searches++;

            if ($this->portalDown) {
                return Http::response('', 503);
            }
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);

            $rows = collect($this->portal)->filter(function ($n) use ($q) {
                if (! empty($q['reference'])) {
                    return $n['code'] === $q['reference'];
                }
                if (! empty($q['procedure']) && $n['procedure'] !== $q['procedure']) {
                    return false;
                }
                // Like the real portal: no procedure, no deadline filter.
                if (! empty($q['procedure']) && ! $this->ignoreDeadlineFilter && ! empty($q['tendersDeadlineFrom'])) {
                    $from = Carbon::createFromFormat('d/m/Y H:i', $q['tendersDeadlineFrom']);
                    $to = Carbon::createFromFormat('d/m/Y H:i', $q['tendersDeadlineTo']);

                    return $n['deadline']->between($from, $to);
                }

                return true;
            })->take(100)->values();

            return Http::response($this->listing($rows->all()));
        }]);
    }

    private function listing(array $rows): string
    {
        $html = '<table>';
        foreach ($rows as $i => $n) {
            $html .= "<tr><td><span id=\"g_spnMatchingResultReference_{$i}\">{$n['code']}</span>"
                ."<span id=\"g_spnMatchingResultDescription_{$i}\">Aviso {$n['code']}</span>"
                ."<span id=\"g_spnMatchingResultAuthorityName_{$i}\">Institución</span>"
                ."<div id=\"dtmbNationalOfficialPublishingDate_{$i}_txt\"><span>".now()->subDay()->format('d/m/Y H:i').'</span></div>'
                ."<div id=\"dtmbDueDateForReceivingReplies_{$i}_txt\"><span>".$n['deadline']->format('d/m/Y H:i').'</span></div>'
                ."<span id=\"cbxBasePriceValue_{$i}\">1,000.00 Dominican Pesos</span>"
                ."<span id=\"g_spnMatchingResultContractNoticeState_{$i}\">{$n['status']}</span>"
                ."<a onclick=\"go('Detail?noticeUID=' + 'DO1.NTC.".(1000 + $i)."')\">Detail</a></td></tr>";
        }

        return $html.'</table>';
    }
}
