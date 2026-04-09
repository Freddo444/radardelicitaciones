<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanySwitchController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user, 403);
        $companies = $user->companies()->withCount('users')->get();

        return view('companies.index', compact('companies'));
    }

    public function switch(Request $request, Company $company)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user && $user->belongsToCompany($company->id), 403);

        session(['current_company_id' => $company->id]);
        $user->update(['current_company_id' => $company->id]);

        return redirect()->route('dashboard')
            ->with('success', "Cambiado a {$company->razon_social}");
    }

    public function create()
    {
        return view('companies.create');
    }

    public function store(Request $request)
    {
        $subscription = Subscription::where('user_id', Auth::id())->first();
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user, 403);
        abort_unless($subscription, 403, 'No tienes una suscripción activa para crear empresas.');
        abort_unless(SubscriptionService::canAddCompany($subscription), 422, 'Has alcanzado el límite de empresas de tu plan.');
        $validated = $request->validate([
            'razon_social' => 'required|string|max:255',
            'rnc' => 'required|string|max:20',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'municipio' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
        ]);

        $company = Company::create(array_merge($validated, ['owner_id' => Auth::id()]));

        // Attach the current user to the new company
        $user->companies()->attach($company->id, ['joined_at' => now()]);

        // Auto-switch to the new company
        session(['current_company_id' => $company->id]);
        $user->update(['current_company_id' => $company->id]);

        return redirect()->route('dashboard')
            ->with('success', "Empresa \"{$company->razon_social}\" creada.");
    }
}
