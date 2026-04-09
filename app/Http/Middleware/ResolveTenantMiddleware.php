<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Super-admin impersonation override
        if ($user->isSuperAdmin() && session()->has('impersonating_company_id')) {
            $company = Company::find(session('impersonating_company_id'));
            if ($company) {
                $this->bindCompany($company);

                return $next($request);
            }
            session()->forget(['impersonating_company_id', 'impersonating_user_name']);
        }

        $companyId = session('current_company_id') ?? $user->current_company_id;

        // Validate that user belongs to this company
        if ($companyId && $user->belongsToCompany($companyId)) {
            $company = Company::find($companyId);
            if ($company) {
                $this->bindCompany($company);
                // Sync to session and user record
                if (session('current_company_id') !== $companyId) {
                    session(['current_company_id' => $companyId]);
                }
                if ($user->current_company_id !== $companyId) {
                    $user->update(['current_company_id' => $companyId]);
                }

                return $next($request);
            }
        }

        // Try to auto-select if user has exactly one company
        $companies = $user->companies;
        if ($companies->count() === 1) {
            $company = $companies->first();
            session(['current_company_id' => $company->id]);
            $user->update(['current_company_id' => $company->id]);
            $this->bindCompany($company);

            return $next($request);
        }

        // Multiple companies — redirect to switcher
        if ($companies->count() > 1) {
            return redirect()->route('companies.index');
        }

        // No companies — redirect to company setup
        return redirect()->route('companies.create');
    }

    private function bindCompany(Company $company): void
    {
        app()->instance('currentCompany', $company);
        view()->share('currentCompany', $company);
    }
}
