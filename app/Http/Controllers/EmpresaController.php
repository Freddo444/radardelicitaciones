<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function index()
    {
        $company = Company::instance();

        return view('empresa.index', compact('company'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'rnc' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:500',
            'municipio' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'web' => 'nullable|url|max:255',
            'rep_legal_nombre' => 'nullable|string|max:255',
            'rep_legal_cedula' => 'nullable|string|max:20',
            'rep_legal_cargo' => 'nullable|string|max:100',
            'rpe_numero' => 'nullable|string|max:50',
            'rpe_vence' => 'nullable|date',
            'cpa_numero' => 'nullable|string|max:50',
            'cpa_vence' => 'nullable|date',
        ]);

        $company = Company::instance();
        $company->fill($data);
        $company->save();

        return redirect()->route('empresa.index')->with('success', 'Perfil de empresa actualizado.');
    }
}
