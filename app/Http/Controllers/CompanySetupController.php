<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Rubro;
use App\Services\BidMatchingService;
use App\Services\DgcpProviderService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CompanySetupController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $subscription = $user->subscription;

        if (! $subscription || ! $subscription->isActive()) {
            return redirect()->route('billing.index')
                ->with('warning', 'Completa el pago antes de configurar tu empresa.');
        }

        if (! SubscriptionService::canAddCompany($subscription)) {
            return redirect()->route('companies.index')
                ->with('error', 'Has alcanzado el límite de empresas de tu plan.');
        }

        $isTrial = $subscription->isTrialing();

        return view('company-setup.create', compact('isTrial'));
    }

    /**
     * AJAX: Lookup company info + rubros from DGCP API by RPE.
     */
    public function lookupRpe(Request $request)
    {
        $request->validate(['rpe' => 'required|integer|min:1']);

        $rpe = (int) $request->rpe;
        $base = 'https://datosabiertos.dgcp.gob.do/api-dgcp/v1';

        // Fetch provider info
        $providerResponse = Http::timeout(10)->get("{$base}/proveedores", [
            'rpe' => $rpe,
            'limit' => 1,
        ]);

        if ($providerResponse->failed()) {
            return response()->json(['found' => false, 'error' => 'Error al consultar la DGCP.'], 502);
        }

        $providers = $providerResponse->json('payload.content', []);
        if (empty($providers)) {
            return response()->json(['found' => false]);
        }

        $provider = $providers[0];

        // Fetch rubros via the shared service (same source the sync action uses).
        $rubros = app(DgcpProviderService::class)->fetchRubros($rpe);

        $seenCodes = [];
        $rubros = array_values(array_filter($rubros, function (array $r) use (&$seenCodes) {
            $code = $r['code'] ?? '';
            if ($code === '' || isset($seenCodes[$code])) {
                return false;
            }
            $seenCodes[$code] = true;

            return true;
        }));

        return response()->json([
            'found' => true,
            'company' => [
                'razon_social' => $provider['razon_social'] ?? '',
                'rnc' => $provider['numero_documento'] ?? '',
                'telefono' => $provider['telefono_comercial'] ?? '',
                'email' => $provider['correo_comercial'] ?? '',
                'direccion' => $provider['direccion'] ?? '',
                'municipio' => $provider['municipio'] ?? '',
                'provincia' => $provider['provincia'] ?? '',
                'rpe_numero' => $rpe,
                'registro_mercantil' => $provider['numero_registro_mercantil'] ?? '',
            ],
            'rubros' => $rubros,
        ]);
    }

    public function store(Request $request, BidMatchingService $matcher)
    {
        $user = Auth::user();
        $subscription = $user->subscription;

        if (! $subscription || ! $subscription->isActive()) {
            return redirect()->route('billing.index');
        }

        if (! SubscriptionService::canAddCompany($subscription)) {
            return redirect()->route('companies.index')
                ->with('error', 'Has alcanzado el límite de empresas de tu plan.');
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
            'rpe_numero' => 'nullable|integer',
            'registro_mercantil' => 'nullable|string|max:50',
            'rubros' => 'nullable|array',
            'rubros.*.code' => 'required|string|max:20',
            'rubros.*.name' => 'required|string|max:255',
        ]);

        $company = DB::transaction(function () use ($request, $user) {
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
                'rpe_numero' => $request->rpe_numero,
                'registro_mercantil' => $request->registro_mercantil,
            ]);

            $company->users()->attach($user->id, ['joined_at' => now()]);

            if ($request->rubros) {
                $seenCodes = [];
                foreach ($request->rubros as $rubro) {
                    $code = $rubro['code'] ?? '';
                    if ($code === '' || isset($seenCodes[$code])) {
                        continue;
                    }
                    $seenCodes[$code] = true;

                    Rubro::create([
                        'company_id' => $company->id,
                        'code' => $code,
                        'name' => $rubro['name'],
                        'level' => 'familia',
                        'active' => true,
                    ]);
                }
            }

            return $company;
        });

        session(['current_company_id' => $company->id]);
        $user->update(['current_company_id' => $company->id]);

        // Backfill matches against the 90-day bid backlog so the dashboard is
        // populated the moment onboarding finishes, instead of empty until the
        // next poll happens to catch a new bid. Guarded so a slow/failed match
        // never blocks the redirect — a poll will fill it in either way.
        $matched = 0;
        try {
            $matched = $matcher->sondear($company->id);
        } catch (\Throwable $e) {
            Log::warning('[CompanySetup] onboarding sondear failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }

        $message = $matched > 0
            ? "Empresa {$company->razon_social} creada. Encontramos {$matched} convocatoria(s) que coinciden con tus rubros."
            : "Empresa {$company->razon_social} creada correctamente.";

        return redirect()->route('dashboard')
            ->with(array_filter([
                'success' => $message,
                '_umami' => umami_flash_payload('company_onboarding_complete', ['initial_matches' => $matched]),
            ], fn ($v) => $v !== null));
    }
}
