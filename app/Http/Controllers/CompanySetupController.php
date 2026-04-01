<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Rubro;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

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

        // Fetch rubros
        $rubros = [];
        $page = 1;
        do {
            $rubroResponse = Http::timeout(10)->get("{$base}/proveedores/rubro", [
                'rpe' => $rpe,
                'limit' => 100,
                'page' => $page,
            ]);

            if ($rubroResponse->failed()) {
                break;
            }

            $content = $rubroResponse->json('payload.content', []);
            foreach ($content as $r) {
                $rubros[] = [
                    'code' => $r['familia_unspsc'],
                    'name' => trim($r['descripcion']),
                ];
            }

            $totalPages = $rubroResponse->json('pages', 1);
            $page++;
        } while ($page <= $totalPages);

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

    public function store(Request $request)
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

        // Attach user to company
        $company->users()->attach($user->id, ['joined_at' => now()]);

        // Create rubros from DGCP lookup
        if ($request->rubros) {
            foreach ($request->rubros as $rubro) {
                Rubro::create([
                    'company_id' => $company->id,
                    'code' => $rubro['code'],
                    'name' => $rubro['name'],
                    'level' => 'familia',
                    'active' => true,
                ]);
            }
        }

        // Set as current company
        session(['current_company_id' => $company->id]);
        $user->update(['current_company_id' => $company->id]);

        return redirect()->route('dashboard')
            ->with('success', "Empresa {$company->razon_social} creada correctamente.");
    }
}
