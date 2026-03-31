<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;

class AdminSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::with('owner');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('q')) {
            $query->whereHas('owner', fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $subscriptions = $query->latest()->paginate(25)->withQueryString();

        return view('admin.subscriptions.index', compact('subscriptions'));
    }

    public function updateStatus(Request $request, Subscription $subscription)
    {
        $request->validate([
            'status' => 'required|in:pending,active,past_due,cancelled,suspended',
        ]);

        $old = $subscription->status;
        $subscription->update(['status' => $request->status]);

        if ($request->status === 'active' && ! $subscription->current_period_end) {
            $subscription->update([
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
        }

        return back()->with('success', "Suscripcion #{$subscription->id}: {$old} → {$request->status}");
    }
}
