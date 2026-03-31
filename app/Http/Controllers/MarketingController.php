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
}
