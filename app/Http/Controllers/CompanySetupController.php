<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanySetupController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $subscription = $user->subscription;

        // Must have an active subscription to create a company
        if (! $subscription || ! $subscription->isActive()) {
            return redirect()->route('billing.index')
                ->with('warning', 'Completa el pago antes de configurar tu empresa.');
        }

        if (! SubscriptionService::canAddCompany($subscription)) {
            return redirect()->route('companies.index')
                ->with('error', 'Has alcanzado el limite de empresas de tu plan.');
        }

        return view('company-setup.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $subscription = $user->subscription;

        if (! $subscription || ! $subscription->isActive()) {
            return redirect()->route('billing.index');
        }

        if (! SubscriptionService::canAddCompany($subscription)) {
            return redirect()->route('companies.index')
                ->with('error', 'Has alcanzado el limite de empresas de tu plan.');
        }

        $request->validate([
            'razon_social' => 'required|string|max:255',
            'rnc' => 'required|string|max:20',
            'nombre_comercial' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:500',
            'municipio' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:100',
        ]);

        $company = Company::create([
            'owner_id' => $user->id,
            'razon_social' => $request->razon_social,
            'rnc' => $request->rnc,
            'nombre_comercial' => $request->nombre_comercial,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'direccion' => $request->direccion,
            'municipio' => $request->municipio,
            'provincia' => $request->provincia,
        ]);

        // Attach user to company
        $company->users()->attach($user->id, ['joined_at' => now()]);

        // Set as current company
        session(['current_company_id' => $company->id]);
        $user->update(['current_company_id' => $company->id]);

        return redirect()->route('dashboard')
            ->with('success', "Empresa {$company->razon_social} creada correctamente.");
    }
}
