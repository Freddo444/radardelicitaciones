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

        // Claim running state immediately so the progress page doesn't see stale "idle"
        Setting::set('poll_status', 'running');
        Setting::set('poll_log', '[]');
        Setting::set('poll_started_at', now()->toDateTimeString());

        RunPollJob::dispatch();

        return redirect()->route('poll.progress');
    }
}
