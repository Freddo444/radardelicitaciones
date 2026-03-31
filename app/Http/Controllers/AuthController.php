<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->is_super_admin) {
                return redirect()->route('admin.dashboard');
            }

            // Check subscription status first
            $subscription = $user->subscription;
            if ($subscription && ! $subscription->isActive()) {
                return redirect()->route('billing.index');
            }

            $companies = $user->companies;

            if ($companies->count() === 0) {
                // Has active subscription but no company? Go to setup wizard
                if ($subscription && $subscription->isActive()) {
                    return redirect()->route('company-setup.show');
                }

                return redirect()->route('billing.index');
            }

            if ($companies->count() === 1) {
                $company = $companies->first();
                session(['current_company_id' => $company->id]);
                $user->update(['current_company_id' => $company->id]);

                return redirect()->intended(route('dashboard'));
            }

            // Multiple companies — let them pick
            return redirect()->route('companies.index');
        }

        return back()->withErrors([
            'email' => 'Correo o contraseña incorrectos.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
