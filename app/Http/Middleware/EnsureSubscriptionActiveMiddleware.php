<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActiveMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Super-admins bypass subscription check
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Find subscription via the current company's owner
        $company = currentCompany();

        if ($company && $company->owner_id) {
            $subscription = Subscription::where('user_id', $company->owner_id)->first();
        } else {
            // Fallback: user owns the subscription directly
            $subscription = $user->subscription;
        }

        if ($subscription && $subscription->trialExpired()) {
            if ($user->isSubscriptionOwner()) {
                return redirect()->route('billing.index')
                    ->with('warning', 'Tu prueba gratuita ha expirado. Suscríbete para continuar.');
            }

            abort(403, 'La prueba gratuita de tu empresa ha expirado. Contacta al administrador.');
        }

        if (! $subscription || ! $subscription->isActive()) {
            if ($user->isSubscriptionOwner()) {
                return redirect()->route('billing.index')
                    ->with('warning', 'Tu suscripción no está activa. Completa el pago para continuar.');
            }

            abort(403, 'La suscripción de tu empresa no está activa. Contacta al administrador.');
        }

        return $next($request);
    }
}
