<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanySwitchController extends Controller
{
    public function index()
    {
        $companies = auth()->user()->companies()->withCount('users')->get();

        return view('companies.index', compact('companies'));
    }

    public function switch(Request $request, Company $company)
    {
        abort_unless(auth()->user()->belongsToCompany($company->id), 403);

        session(['current_company_id' => $company->id]);
        auth()->user()->update(['current_company_id' => $company->id]);

        return redirect()->route('dashboard')
            ->with('success', "Cambiado a {$company->razon_social}");
    }

    public function create()
    {
        return view('companies.create');
    }

    public function store(Request $request)
    {
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

        $company = Company::create(array_merge($validated, ['owner_id' => auth()->id()]));

        // Attach the current user to the new company
        auth()->user()->companies()->attach($company->id, ['joined_at' => now()]);

        // Auto-switch to the new company
        session(['current_company_id' => $company->id]);
        auth()->user()->update(['current_company_id' => $company->id]);

        return redirect()->route('dashboard')
            ->with('success', "Empresa \"{$company->razon_social}\" creada.");
    }
}
