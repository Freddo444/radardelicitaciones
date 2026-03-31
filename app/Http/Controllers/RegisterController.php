<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Create pending subscription
        Subscription::create([
            'user_id' => $user->id,
            'plan' => 'basic',
            'status' => 'pending',
            'max_companies' => SubscriptionService::BASE_COMPANIES,
            'max_users' => SubscriptionService::BASE_USERS,
            'monthly_amount' => SubscriptionService::BASE_PRICE,
        ]);

        Auth::login($user);

        return redirect()->route('billing.index')
            ->with('info', 'Cuenta creada. Completa el pago para activar tu suscripcion.');
    }
}
