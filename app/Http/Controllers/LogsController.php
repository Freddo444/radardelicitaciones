<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;

class LogsController extends Controller
{
    public function index()
    {
        $logs = NotificationLog::with('bid')
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('logs.index', compact('logs'));
    }
}
