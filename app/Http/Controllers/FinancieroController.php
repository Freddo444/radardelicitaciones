<?php

namespace App\Http\Controllers;

use App\Models\FinancialRecord;
use App\Models\OfferFinancial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FinancieroController extends Controller
{
    public function index()
    {
        $company = currentCompany();

        $records = FinancialRecord::where('company_id', $company->id)
            ->orderByDesc('anio_fiscal')
            ->get();

        return view('financiero.index', compact('records'));
    }

    public function create()
    {
        $company = currentCompany();

        // Suggest next year to add
        $latest = FinancialRecord::where('company_id', $company->id)->max('anio_fiscal');
        $suggested = $latest ? $latest - 1 : date('Y') - 1;

        return view('financiero.create', compact('suggested'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'anio_fiscal' => 'required|integer|min:2000|max:'.date('Y'),
            'currency' => 'required|in:DOP,USD',
        ]);

        $company = currentCompany();

        $record = FinancialRecord::firstOrCreate(
            ['company_id' => $company->id, 'anio_fiscal' => $data['anio_fiscal']],
            ['currency' => $data['currency']]
        );

        return redirect()->route('financiero.show', $record)->with('success', 'Año fiscal creado. Completa los balances.');
    }

    public function show(FinancialRecord $financiero)
    {
        abort_unless($financiero->company_id === currentCompany()->id, 403);

        return view('financiero.show', compact('financiero'));
    }

    public function update(Request $request, FinancialRecord $financiero)
    {
        abort_unless($financiero->company_id === currentCompany()->id, 403);

        $data = $request->validate([
            'currency' => 'required|in:DOP,USD',
            'activo_total' => 'nullable|numeric|min:0',
            'activo_circulante' => 'nullable|numeric|min:0',
            'inventarios' => 'nullable|numeric|min:0',
            'pasivo_total' => 'nullable|numeric|min:0',
            'pasivo_circulante' => 'nullable|numeric|min:0',
            'patrimonio' => 'nullable|numeric',
            'ingresos' => 'nullable|numeric|min:0',
            'utilidad' => 'nullable|numeric',
            'solvencia_override' => 'nullable|numeric|min:0',
            'liquidez_override' => 'nullable|numeric|min:0',
            'endeudamiento_override' => 'nullable|numeric|min:0',
            'capital_trabajo_override' => 'nullable|numeric',
            'override_razon' => 'nullable|string|max:500',
            'notas' => 'nullable|string|max:2000',
        ]);

        $financiero->fill($data)->save();
        $financiero->recalculateIndices();

        return redirect()->route('financiero.show', $financiero)->with('success', 'Balances guardados e índices recalculados.');
    }

    public function uploadDocument(Request $request, FinancialRecord $financiero)
    {
        abort_unless($financiero->company_id === currentCompany()->id, 403);

        $request->validate([
            'tipo' => 'required|in:ir2,estado_financiero',
            'file' => 'required|file|mimes:pdf|max:20480',
        ]);

        $tipo = $request->tipo;
        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();
        $path = $file->storeAs(
            "vault/{$financiero->company_id}/financiero/{$financiero->anio_fiscal}",
            Str::uuid().'.'.$ext,
            'vault'
        );

        $financiero->update([
            "path_{$tipo}" => $path,
            "filename_{$tipo}" => $file->getClientOriginalName(),
        ]);

        return redirect()->route('financiero.show', $financiero)->with('success', 'Documento subido.');
    }

    public function downloadDocument(FinancialRecord $financiero, string $tipo)
    {
        abort_unless($financiero->company_id === currentCompany()->id, 403);
        abort_unless(in_array($tipo, ['ir2', 'estado_financiero']), 404);

        $path = $financiero->{"path_{$tipo}"};
        $filename = $financiero->{"filename_{$tipo}"};

        abort_unless($path && Storage::disk('vault')->exists($path), 404);

        return Storage::disk('vault')->download($path, $filename);
    }

    public function destroy(FinancialRecord $financiero)
    {
        abort_unless($financiero->company_id === currentCompany()->id, 403);

        $activeRef = OfferFinancial::where('financial_record_id', $financiero->id)
            ->whereHas('offer', fn ($q) => $q->whereIn('estado', ['borrador', 'en_preparacion', 'listo']))
            ->with('offer:id,proceso_nombre')
            ->first();

        if ($activeRef) {
            return back()->with('error', "Este registro está en uso por la oferta \"{$activeRef->offer->proceso_nombre}\". Desactívalo en lugar de eliminarlo.");
        }

        foreach (['ir2', 'estado_financiero'] as $tipo) {
            if ($financiero->{"path_{$tipo}"}) {
                Storage::disk('vault')->delete($financiero->{"path_{$tipo}"});
            }
        }

        $financiero->delete();

        return redirect()->route('financiero.index')->with('success', 'Año fiscal eliminado.');
    }
}
