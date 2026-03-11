<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunPollJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public $timeout = 3600; // 1 hour — large clients with many familias can take 20-40 min

    public function handle(): void
    {
        \Artisan::call('secp:poll');
    }
}
