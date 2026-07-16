<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\Rubro;
use App\Services\BidMatchingService;
use App\Services\DgcpProviderService;
use Illuminate\Http\Request;

class RubrosController extends Controller
{
    public function index()
    {
        $rubros = Rubro::orderByDesc('active')->orderBy('name')->paginate(25);
        $company = currentCompany();

        return view('rubros.index', compact('rubros', 'company'));
    }

    /**
     * Re-sync the company's rubros from its DGCP registration. Adds any rubro
     * the company has newly registered at the DGCP (familia level), leaving
     * existing rubros and their active state untouched, then re-matches so new
     * rubros immediately surface historical convocatorias.
     */
    public function sync(DgcpProviderService $dgcp, BidMatchingService $matcher)
    {
        $company = currentCompany();

        if (! $company->rpe_numero) {
            return back()->with('warning', 'Agrega tu número RPE en el perfil de empresa para sincronizar rubros con la DGCP.');
        }

        $remote = $dgcp->fetchRubros((int) $company->rpe_numero);
        if (empty($remote)) {
            return back()->with('info', 'No se encontraron rubros en la DGCP para tu RPE, o la DGCP no respondió. Intenta de nuevo más tarde.');
        }

        $existing = Rubro::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->pluck('code')
            ->flip();

        $added = 0;
        foreach ($remote as $r) {
            if ($existing->has($r['code'])) {
                continue;
            }
            Rubro::create([
                'company_id' => $company->id,
                'code' => $r['code'],
                'name' => $r['name'],
                'level' => 'familia',
                'active' => true,
            ]);
            $added++;
        }

        if ($added === 0) {
            return back()->with('info', 'Tus rubros ya están al día con la DGCP.');
        }

        $matched = $matcher->sondear($company->id);

        $msg = "Se agregaron {$added} rubro(s) desde la DGCP.";
        if ($matched > 0) {
            $msg .= " Encontramos {$matched} convocatoria(s) nueva(s) que coinciden.";
        }

        return back()->with('success', $msg);
    }

    public function store(Request $request)
    {
        $companyId = currentCompany()->id;
        $request->validate([
            'code' => "required|string|max:20|unique:rubros,code,NULL,id,company_id,{$companyId}",
            'name' => 'required|string|max:255',
            'level' => 'required|in:familia,clase,subclase',
        ]);

        Rubro::create([
            'company_id' => $companyId,
            'code' => $request->code,
            'name' => $request->name,
            'level' => $request->level,
            'active' => true,
        ]);

        return back()->with('success', "Rubro {$request->code} agregado correctamente.");
    }

    public function destroy($rubro)
    {
        $rubro = Rubro::findOrFail($rubro);
        $code = $rubro->code;
        $rubro->delete();

        return back()->with('success', "Rubro {$code} eliminado.");
    }

    public function toggle($rubro)
    {
        $rubro = Rubro::findOrFail($rubro);
        $rubro->update(['active' => ! $rubro->active]);

        return back();
    }

    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        if (! ctype_digit($q)) {
            return response()->json([]);
        }

        // Exact 8-digit code — detect level by trailing zeros
        if (strlen($q) === 8) {
            if (str_ends_with($q, '000000')) {
                return response()->json([]); // segmento not registerable
            } elseif (str_ends_with($q, '0000')) {
                $row = CatalogItem::where('familia', $q)->first();
                if (! $row) {
                    return response()->json([]);
                }

                return response()->json([[
                    'code' => $row->familia,
                    'name' => $row->descripcion_familia,
                    'level' => 'familia',
                ]]);
            } elseif (str_ends_with($q, '00')) {
                $row = CatalogItem::where('clase', $q)->first();
                if (! $row) {
                    return response()->json([]);
                }

                return response()->json([[
                    'code' => $row->clase,
                    'name' => $row->descripcion_clase,
                    'level' => 'clase',
                ]]);
            } else {
                $row = CatalogItem::where('subclase', $q)->first();
                if (! $row) {
                    return response()->json([]);
                }

                return response()->json([[
                    'code' => $row->subclase,
                    'name' => $row->descripcion_subclase,
                    'level' => 'subclase',
                ]]);
            }
        }

        return response()->json([]);
    }
}
