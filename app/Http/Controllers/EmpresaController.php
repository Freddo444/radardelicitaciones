<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function index()
    {
        $company = currentCompany();

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
            'rep_legal_nacionalidad' => 'nullable|string|max:100',
            'rep_legal_estado_civil' => 'nullable|string|max:50',
            'rpe_numero' => 'nullable|string|max:50',
            'registro_mercantil' => 'nullable|string|max:50',
            'cpa_numero' => 'nullable|string|max:50',
            'cpa_vence' => 'nullable|date',
        ]);

        $company = currentCompany();
        $company->fill($data);
        $company->save();

        return redirect()->route('empresa.index')->with('success', 'Perfil de empresa actualizado.');
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'type' => 'required|in:firma,sello,logo',
            'image' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $type = $request->type;
        $column = $type.'_path';
        $company = currentCompany();

        // Delete old file if exists
        if ($company->$column && \Storage::disk('public')->exists($company->$column)) {
            \Storage::disk('public')->delete($company->$column);
        }

        $path = $request->file('image')->store('empresa', 'public');
        $company->$column = $path;
        $company->save();

        return back()->with('success', match ($type) {
            'firma' => 'Firma actualizada.',
            'sello' => 'Sello actualizado.',
            'logo' => 'Logo actualizado.',
        });
    }

    public function deleteImage(Request $request)
    {
        $request->validate(['type' => 'required|in:firma,sello,logo']);

        $type = $request->type;
        $column = $type.'_path';
        $company = currentCompany();

        if ($company->$column && \Storage::disk('public')->exists($company->$column)) {
            \Storage::disk('public')->delete($company->$column);
        }

        $company->$column = null;
        $company->save();

        return back()->with('success', 'Imagen eliminada.');
    }
}
