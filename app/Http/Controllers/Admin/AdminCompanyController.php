<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Rubro;
use App\Models\Subscription;
use Illuminate\Http\Request;

class AdminCompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::withCount('users', 'rubros', 'offers');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('razon_social', 'like', "%{$search}%")
                    ->orWhere('rnc', 'like', "%{$search}%");
            });
        }

        $companies = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('admin.companies.index', compact('companies'));
    }

    public function show(Company $company)
    {
        $company->loadCount('users', 'rubros', 'offers');
        $users = $company->users;
        $rubros = Rubro::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('active', true)
            ->get();
        $recentOffers = $company->offers()
            ->withoutGlobalScopes()
            ->latest()
            ->limit(5)
            ->get();

        // Find subscription through company owner
        $ownerIds = $company->users()->pluck('users.id');
        $subscription = Subscription::whereIn('user_id', $ownerIds)->first();

        return view('admin.companies.show', compact('company', 'users', 'rubros', 'recentOffers', 'subscription'));
    }

    public function impersonate(Company $company)
    {
        session([
            'impersonating_company_id' => $company->id,
        ]);
        session()->forget('impersonating_user_name');

        return redirect()->route('dashboard')
            ->with('info', "Impersonando {$company->razon_social}.");
    }

    public function stopImpersonation()
    {
        session()->forget(['impersonating_company_id', 'impersonating_user_name']);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Impersonacion detenida.');
    }
}
