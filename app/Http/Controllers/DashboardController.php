<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use App\Models\Company;
use App\Models\FinancialRecord;
use App\Models\Offer;
use App\Models\OfferEvent;
use App\Models\Personnel;
use App\Models\Project;
use App\Models\Setting;
use App\Models\VaultDocument;

class DashboardController extends Controller
{
    public function index()
    {
        $company = Company::instance();
        $showAll = request()->boolean('ver');

        // ── Expiry alerts ─────────────────────────────────────────────
        $expiryAlerts = VaultDocument::where('company_id', $company->id)
            ->where('is_current', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(30))
            ->orderBy('expires_at')
            ->get();

        // ── Active offers ─────────────────────────────────────────────
        $activeOffers = Offer::where('company_id', $company->id)
            ->whereIn('estado', ['borrador', 'en_preparacion', 'listo'])
            ->orderByRaw("FIELD(estado, 'en_preparacion', 'listo', 'borrador')")
            ->orderBy('fecha_limite')
            ->get();

        // ── Upcoming deadlines from offer_events ─────────────────────
        $upcomingEvents = OfferEvent::where('status', 'pending')
            ->where('event_date', '>=', now())
            ->where('event_date', '<=', now()->addDays(14))
            ->whereHas('offer', fn ($q) => $q->where('company_id', $company->id)
                ->whereIn('estado', ['borrador', 'en_preparacion', 'listo']))
            ->with('offer:id,proceso_nombre')
            ->orderBy('event_date')
            ->limit(10)
            ->get();

        // ── Vault summary stats ───────────────────────────────────────
        $vaultStats = [
            'personnel' => Personnel::where('company_id', $company->id)->where('active', true)->count(),
            'projects' => Project::where('company_id', $company->id)->count(),
            'documents' => VaultDocument::where('company_id', $company->id)->where('is_current', true)->count(),
            'financials' => FinancialRecord::where('company_id', $company->id)->count(),
        ];

        // ── Poll status ───────────────────────────────────────────────
        $lastPolledAt = Setting::get('last_polled_at');
        $pollIntervalMins = (int) (Setting::get('poll_interval_minutes') ?? 60);

        // ── Bid feed (respects settings filters) ─────────────────────
        $bidQuery = Bid::filtered();
        $bidStats = [
            'total' => (clone $bidQuery)->count(),
            'this_week' => (clone $bidQuery)->where('published_at', '>=', now()->startOfWeek())->count(),
            'unnotified' => (clone $bidQuery)->whereNull('notified_at')->count(),
        ];

        if ($showAll) {
            $bids = (clone $bidQuery)->orderByDesc('published_at')->paginate(25);
            $recentBids = null;
        } else {
            $recentBids = (clone $bidQuery)->orderByDesc('published_at')->limit(7)->get();
            $bids = null;
        }

        return view('dashboard.index', compact(
            'expiryAlerts', 'activeOffers', 'upcomingEvents', 'vaultStats',
            'lastPolledAt', 'pollIntervalMins',
            'bidStats', 'recentBids', 'bids', 'showAll'
        ));
    }
}
