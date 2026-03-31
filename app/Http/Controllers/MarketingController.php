<?php

namespace App\Http\Controllers;

class MarketingController extends Controller
{
    public function landing()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return view('marketing.landing');
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

    public function sitemap()
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => url('/precios'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => url('/terminos'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => url('/privacidad'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => url('/registro'), 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['loc' => url('/login'), 'priority' => '0.5', 'changefreq' => 'yearly'],
        ];

        return response()->view('marketing.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
