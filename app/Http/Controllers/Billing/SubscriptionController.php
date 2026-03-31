<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $subscription = $user->subscription;

        if (! $subscription) {
            return redirect()->route('register.show')
                ->with('error', 'No tienes una suscripcion. Registrate primero.');
        }

        $usage = SubscriptionService::usage($subscription);
        $payments = $subscription->payments()->limit(10)->get();

        return view('billing.index', compact('subscription', 'usage', 'payments'));
    }

    public function cancel(Request $request)
    {
        $user = Auth::user();
        $subscription = $user->subscription;

        if (! $subscription || ! $user->isSubscriptionOwner()) {
            abort(403);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Suscripcion cancelada.');
    }
}
