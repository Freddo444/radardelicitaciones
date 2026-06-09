<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MarketingController extends Controller
{
    public function landing()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $stats = Cache::remember('landing_stats', 3600, function () {
            return [
                'bids' => DB::table('bids')->count(),
                'institutions' => DB::table('bids')->distinct('buyer_name')->count('buyer_name'),
                'rubros' => DB::table('rubros')->distinct('code')->count('code'),
            ];
        });

        return view('marketing.landing', compact('stats'));
    }

    public function pricing()
    {
        return view('marketing.pricing');
    }

    public function terms()
    {
        return view('marketing.terms');
    }

    public function privacy()
    {
        return view('marketing.privacy');
    }

    public function paymentPolicies()
    {
        return view('marketing.payment-policies');
    }

    public function sitemap()
    {
        $today = now()->toDateString();

        // Sitemap advertises pages worth indexing. /login is a utility page
        // with no SEO value; /registro is a transit page that redirects to the
        // trial signup when traffic carries a UTM (which is what our campaigns
        // send). Both were causing "Discovered - currently not indexed" in
        // Google Search Console, so we drop them and let Google focus its
        // crawl budget on the pages we actually want to rank.
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'weekly', 'lastmod' => $today],
            ['loc' => url('/precios'), 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $today],
            ['loc' => url('/registro/prueba-gratis'), 'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => $today],
            ['loc' => url('/terminos'), 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => $today],
            ['loc' => url('/privacidad'), 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => $today],
            ['loc' => url('/politicas-pago-seguridad'), 'priority' => '0.4', 'changefreq' => 'yearly', 'lastmod' => $today],
        ];

        return response()->view('marketing.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
