<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use App\Models\FinancialRecord;
use App\Models\Offer;
use App\Models\OfferEvent;
use App\Models\Personnel;
use App\Models\Project;
use App\Models\Setting;
use App\Models\VaultDocument;
use App\Services\OnboardingService;

class DashboardController extends Controller
{
    public function index()
    {
        $company = currentCompany();
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

        // ── Bid feed (company-scoped, respects settings filters) ─────
        $bidQuery = Bid::forCompany($company->id)->filtered($company->id);
        $bidStats = [
            'total' => (clone $bidQuery)->count(),
            'this_week' => (clone $bidQuery)->where('bids.published_at', '>=', now()->startOfWeek())->count(),
            'unnotified' => (clone $bidQuery)->whereNull('company_bid.notified_at')->count(),
        ];

        if ($showAll) {
            $bids = (clone $bidQuery)->orderByDesc('bids.published_at')->paginate(25);
            $recentBids = null;
        } else {
            $recentBids = (clone $bidQuery)->orderByDesc('bids.published_at')->limit(7)->get();
            $bids = null;
        }

        $onboarding = OnboardingService::getStatus($company);

        return view('dashboard.index', compact(
            'expiryAlerts', 'activeOffers', 'upcomingEvents', 'vaultStats',
            'lastPolledAt',
            'bidStats', 'recentBids', 'bids', 'showAll', 'onboarding'
        ));
    }
}
