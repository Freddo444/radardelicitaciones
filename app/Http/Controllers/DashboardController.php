<?php

namespace App\Http\Controllers;

use App\Models\Bid;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total' => Bid::count(),
            'this_week' => Bid::where('published_at', '>=', now()->subDays(7))->count(),
            'unnotified' => Bid::whereNull('notified_at')->count(),
        ];

        $bids = Bid::orderByDesc('published_at')->paginate(25);

        return view('dashboard.index', compact('stats', 'bids'));
    }
}
