<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'tipo' => 'sometimes|in:titulares,prueba',
            'q' => 'nullable|string|max:255',
        ]);

        $tipo = $request->input('tipo', 'titulares');

        $query = User::query()
            ->where('is_super_admin', false)
            ->with(['subscription'])
            ->withCount('companies');

        if ($tipo === 'prueba') {
            $query->whereHas('subscription', fn ($q) => $q->where('status', 'trialing'));
        } else {
            $query->whereHas('subscription', fn ($q) => $q->where('status', '!=', 'trialing'));
        }

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('admin.users.index', compact('users', 'tipo'));
    }

    public function show(User $user)
    {
        if ($user->is_super_admin) {
            abort(404);
        }

        $user->load(['subscription', 'companies']);

        $extraOwned = Company::where('owner_id', $user->id)
            ->whereNotIn('id', $user->companies->pluck('id'))
            ->orderBy('id')
            ->get();

        $allCompanies = $user->companies->concat($extraOwned);

        return view('admin.users.show', compact('user', 'allCompanies'));
    }

    public function impersonate(User $user)
    {
        if ($user->is_super_admin) {
            abort(403);
        }

        $company = $this->resolveImpersonationCompany($user);
        if (! $company) {
            return back()->with('error', 'Este usuario no tiene empresa asociada. No se puede impersonar hasta que configure una empresa.');
        }

        session([
            'impersonating_company_id' => $company->id,
            'impersonating_user_name' => $user->name,
        ]);

        return redirect()->route('dashboard')
            ->with('info', "Impersonando {$company->razon_social} (vista como {$user->name}).");
    }

    private function resolveImpersonationCompany(User $user): ?Company
    {
        if ($user->current_company_id) {
            $company = Company::find($user->current_company_id);
            if ($company) {
                return $company;
            }
        }

        $fromPivot = $user->companies()->orderBy('companies.id')->first();
        if ($fromPivot) {
            return $fromPivot;
        }

        return Company::where('owner_id', $user->id)->orderBy('id')->first();
    }
}
