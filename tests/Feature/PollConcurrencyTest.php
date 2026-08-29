<?php

namespace Tests\Feature;

use App\Models\Bid;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PollConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_storing_a_bid_twice_does_not_throw_a_duplicate_key_error(): void
    {
        // The poll and the scrape both write bids. The poll builds its list of
        // "new" process codes, then inserts; the scrape can store one of those
        // codes in between. That used to raise SQLSTATE[23000] and abort the run.
        $attributes = [
            'ocid' => 'ocds-6550wx-UNIHSAM-DAF-CD-2026-0004',
            'title' => 'Adquisición de equipos',
            'buyer_name' => 'UNIHSAM',
        ];

        $first = Bid::firstOrCreate(['process_code' => 'UNIHSAM-DAF-CD-2026-0004'], $attributes);
        $second = Bid::firstOrCreate(['process_code' => 'UNIHSAM-DAF-CD-2026-0004'], $attributes);

        $this->assertTrue($first->wasRecentlyCreated);
        $this->assertFalse($second->wasRecentlyCreated, 'The second write must reuse the existing row');
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Bid::where('process_code', 'UNIHSAM-DAF-CD-2026-0004')->count());
    }

    public function test_process_code_is_unique_so_a_plain_insert_would_still_collide(): void
    {
        // Guards the assumption the fix rests on: the constraint is real, so any
        // future plain create() here would reintroduce the crash.
        Bid::create(['process_code' => 'DUP-TEST-1', 'title' => 'x']);

        $this->expectException(QueryException::class);
        Bid::create(['process_code' => 'DUP-TEST-1', 'title' => 'y']);
    }
}
