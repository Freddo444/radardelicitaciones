<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureUtmAttributionMiddleware
{
    private const UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        $captured = [];
        foreach (self::UTM_KEYS as $key) {
            if ($request->filled($key)) {
                $captured[$key] = mb_substr((string) $request->input($key), 0, 120);
            }
        }

        if (! empty($captured) && ! $request->session()->has('attribution')) {
            $request->session()->put('attribution', $captured);
            $request->session()->put('attribution_landed_at', now()->toIso8601String());
        }

        return $next($request);
    }
}
