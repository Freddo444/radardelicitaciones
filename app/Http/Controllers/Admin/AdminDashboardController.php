<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

        $systemHealth = $this->buildSystemHealth();

        return view('admin.dashboard', compact('stats', 'recentPayments', 'pendingTransfers', 'systemHealth'));
    }

    private function buildSystemHealth(): array
    {
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        $oldestJobTs = DB::table('jobs')->min('created_at');
        $failedByQueue = DB::table('failed_jobs')
            ->select('queue', DB::raw('count(*) as count'))
            ->groupBy('queue')
            ->orderByDesc('count')
            ->get();

        $lastPolledAt = Setting::get('last_polled_at');
        $lastScrapedAt = Setting::get('last_scraped_at');
        $pollStatus = Setting::get('poll_status', 'idle');
        $pollStartedAt = Setting::get('poll_started_at');

        // Detect stuck poll: running for more than 90 minutes
        $pollStuck = false;
        if ($pollStatus === 'running' && $pollStartedAt) {
            $pollStuck = now()->diffInMinutes(Carbon::parse($pollStartedAt)) > 90;
        }

        // Staleness in minutes for timestamp indicators
        $pollAgeMin = $lastPolledAt ? now()->diffInMinutes(Carbon::parse($lastPolledAt)) : null;
        $scrapeAgeMin = $lastScrapedAt ? now()->diffInMinutes(Carbon::parse($lastScrapedAt)) : null;

        // Worker inference: if pending jobs are more than 5 minutes old, worker may be down
        $workerDown = $oldestJobTs && (now()->timestamp - $oldestJobTs) > 300;

        // Overall status: ok / warning / critical
        $status = 'ok';
        if ($failedJobs >= 10 || $pollStuck || ($pollAgeMin !== null && $pollAgeMin > 360) || $workerDown) {
            $status = 'critical';
        } elseif ($failedJobs > 0 || ($pollAgeMin !== null && $pollAgeMin > 120)) {
            $status = 'warning';
        }

        return compact(
            'pendingJobs', 'failedJobs', 'failedByQueue',
            'lastPolledAt', 'lastScrapedAt', 'pollStatus', 'pollStuck',
            'pollAgeMin', 'scrapeAgeMin',
            'workerDown', 'oldestJobTs', 'status'
        );
    }
}
