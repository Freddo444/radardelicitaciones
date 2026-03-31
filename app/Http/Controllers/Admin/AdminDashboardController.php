<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'companies' => Company::count(),
            'users' => User::count(),
            'subscriptions_active' => Subscription::where('status', 'active')->count(),
            'subscriptions_pending' => Subscription::where('status', 'pending')->count(),
            'mrr' => Subscription::where('status', 'active')->sum('monthly_amount'),
            'signups_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        $recentPayments = Payment::with('subscription.owner')
            ->latest('created_at')
            ->limit(10)
            ->get();

        $pendingTransfers = Payment::where('gateway', 'bank_transfer')
            ->where('status', 'pending')
            ->with('subscription.owner')
            ->latest()
            ->get();

        $pollHealth = [
            'last_polled_at' => Setting::get('last_polled_at'),
            'poll_status' => Setting::get('poll_status', 'idle'),
        ];

        return view('admin.dashboard', compact('stats', 'recentPayments', 'pendingTransfers', 'pollHealth'));
    }
}
