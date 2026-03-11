<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\Rubro;
use App\Services\DgcpApiClient;
use Illuminate\Http\Request;

class RubrosController extends Controller
{
    public function index()
    {
        $rubros = Rubro::orderByDesc('active')->orderBy('name')->get();
        return view('rubros.index', compact('rubros'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'  => 'required|string|max:20|unique:rubros,code',
            'name'  => 'required|string|max:255',
            'level' => 'required|in:familia,clase,subclase',
        ]);

        Rubro::create([
            'code'   => $request->code,
            'name'   => $request->name,
            'level'  => $request->level,
            'active' => true,
        ]);

        return back()->with('success', "Rubro {$request->code} agregado correctamente.");
    }

    public function destroy($rubro)
    {
        $rubro = Rubro::findOrFail($rubro);
        $code  = $rubro->code;
        $rubro->delete();
        return back()->with('success', "Rubro {$code} eliminado.");
    }

    public function toggle($rubro)
    {
        $rubro = Rubro::findOrFail($rubro);
        $rubro->update(['active' => !$rubro->active]);
        return back();
    }

    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        if (!ctype_digit($q)) {
            return response()->json([]);
        }

        // Exact 8-digit code — detect level by trailing zeros
        if (strlen($q) === 8) {
            if (str_ends_with($q, '000000')) {
                return response()->json([]); // segmento not registerable
            } elseif (str_ends_with($q, '0000')) {
                $row = CatalogItem::where('familia', $q)->first();
                if (!$row) return response()->json([]);
                return response()->json([[
                    'code'  => $row->familia,
                    'name'  => $row->descripcion_familia,
                    'level' => 'familia',
                ]]);
            } elseif (str_ends_with($q, '00')) {
                $row = CatalogItem::where('clase', $q)->first();
                if (!$row) return response()->json([]);
                return response()->json([[
                    'code'  => $row->clase,
                    'name'  => $row->descripcion_clase,
                    'level' => 'clase',
                ]]);
            } else {
                $row = CatalogItem::where('subclase', $q)->first();
                if (!$row) return response()->json([]);
                return response()->json([[
                    'code'  => $row->subclase,
                    'name'  => $row->descripcion_subclase,
                    'level' => 'subclase',
                ]]);
            }
        }

        return response()->json([]);
    }
}
