<?php

namespace App\Http\Controllers;

use App\Models\Setting;

class PollProgressController extends Controller
{
    public function show()
    {
        return view('poll.progress');
    }

    public function status()
    {
        return response()->json([
            'running' => Setting::get('poll_status') === 'running',
            'log' => json_decode(Setting::get('poll_log', '[]'), true) ?: [],
            'started_at' => Setting::get('poll_started_at'),
            'last_polled_at' => Setting::get('last_polled_at'),
        ]);
    }
}
