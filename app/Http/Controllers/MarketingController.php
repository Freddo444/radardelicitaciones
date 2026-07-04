<?php

namespace App\Http\Controllers;

use App\Support\Blog\ArticleRepository;
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

    public function sitemap(ArticleRepository $articles)
    {
        $today = now()->toDateString();

        $urls = [
            ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'weekly', 'lastmod' => $today],
            ['loc' => url('/precios'), 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $today],
            ['loc' => url('/blog'), 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => $today],
            ['loc' => url('/registro/prueba-gratis'), 'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => $today],
            ['loc' => url('/terminos'), 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => $today],
            ['loc' => url('/privacidad'), 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => $today],
            ['loc' => url('/politicas-pago-seguridad'), 'priority' => '0.4', 'changefreq' => 'yearly', 'lastmod' => $today],
        ];

        // Articles: each published post gets its own sitemap entry with its
        // own lastmod so Google knows when to recrawl edited posts.
        foreach ($articles->published() as $article) {
            $urls[] = [
                'loc' => $article->url(),
                'priority' => '0.7',
                'changefreq' => 'monthly',
                'lastmod' => $article->updatedAt->toDateString(),
            ];
        }

        return response()->view('marketing.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
