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
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La prueba gratuita de tu empresa ha expirado. Renueva para continuar.',
                ], 402);
            }

            if ($user->isSubscriptionOwner()) {
                return redirect()->route('billing.index')
                    ->with('warning', 'Tu prueba gratuita ha expirado. Suscríbete para continuar.');
            }

            return redirect()->route('billing.index')
                ->with('warning', 'La prueba gratuita de tu empresa ha expirado. Contacta al administrador de la cuenta.');
        }

        if (! $subscription || ! $subscription->isActive()) {
            // A submitted bank transfer awaiting confirmation gets a dedicated
            // "pending" wall instead of a generic "not active" warning, so the
            // user isn't left in limbo. (Trial users never reach here — an
            // active trial makes isActive() true above.)
            if ($subscription && $subscription->hasPendingBankTransfer()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Tu pago está pendiente de confirmación. Te avisaremos por correo.',
                    ], 402);
                }

                return redirect()->route('billing.transfer-pending');
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La suscripción de tu empresa no está activa. Contacta al administrador.',
                ], 402);
            }

            if ($user->isSubscriptionOwner()) {
                return redirect()->route('billing.index')
                    ->with('warning', 'Tu suscripción no está activa. Completa el pago para continuar.');
            }

            return redirect()->route('billing.index')
                ->with('warning', 'La suscripción de tu empresa no está activa. Contacta al administrador de la cuenta.');
        }

        return $next($request);
    }
}
