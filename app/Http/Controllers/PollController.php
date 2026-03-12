<?php

namespace App\Http\Controllers;

use App\Jobs\RunPollJob;
use App\Models\Setting;

class PollController extends Controller
{
    public function manual()
    {
        // Don't double-dispatch if already running
        if (Setting::get('poll_status') === 'running') {
            return redirect()->route('poll.progress');
        }

        RunPollJob::dispatch();

        return redirect()->route('poll.progress');
    }
}
