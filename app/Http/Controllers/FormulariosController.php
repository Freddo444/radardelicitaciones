<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\OfferGeneratedFile;
use App\Models\Personnel;
use App\Models\Project;
use App\Services\FormGeneratorService;
use Illuminate\Http\Request;

class FormulariosController extends Controller
{
    public function index()
    {
        $company = Company::instance();

        $generated = OfferGeneratedFile::whereNull('offer_id')
            ->latest('generated_at')
            ->take(50)
            ->get();

        $personnel = Personnel::where('company_id', $company->id)->active()->orderBy('nombre')->get();
        $projects = Project::where('company_id', $company->id)->orderByDesc('fecha_inicio')->get();

        return view('formularios.index', compact('generated', 'personnel', 'projects'));
    }

    public function generate(Request $request, FormGeneratorService $generator)
    {
        $request->validate([
            'form_code' => 'required|string',
            'personnel_id' => 'nullable|integer|exists:personnel,id',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'integer|exists:projects,id',
            'proceso_ref' => 'nullable|string|max:100',
            'proceso_nombre' => 'nullable|string|max:500',
            'proceso_tipo' => 'nullable|string|max:100',
            'entidad_nombre' => 'nullable|string|max:255',
            'obra_nombre' => 'nullable|string|max:500',
            'cargo_propuesto' => 'nullable|string|max:255',
            'rep_nacionalidad' => 'nullable|string|max:100',
            'rep_estado_civil' => 'nullable|string|max:50',
        ]);

        $formCode = $request->form_code;

        if (! array_key_exists($formCode, OfferGeneratedFile::$forms)) {
            return back()->withErrors(['form_code' => 'Formulario no reconocido.']);
        }

        $params = array_filter($request->only([
            'personnel_id', 'project_ids',
            'proceso_ref', 'proceso_nombre', 'proceso_tipo',
            'entidad_nombre', 'obra_nombre',
            'cargo_propuesto', 'rep_nacionalidad', 'rep_estado_civil',
        ]), fn ($v) => $v !== null && $v !== '');

        try {
            $file = $generator->generate($formCode, $params);
        } catch (\Exception $e) {
            return back()->withErrors(['form_code' => 'Error al generar: '.$e->getMessage()]);
        }

        return redirect()->route('formularios.index')
            ->with('success', 'Generado: '.OfferGeneratedFile::$forms[$formCode])
            ->with('download_id', $file->id);
    }

    public function download(OfferGeneratedFile $formulario)
    {
        $fullPath = storage_path('app/'.$formulario->path);
        abort_unless(file_exists($fullPath), 404);

        $slug = str_replace(['.', ' '], '-', strtolower($formulario->form_code));
        $filename = $slug.'_'.$formulario->generated_at->format('Ymd').'.docx';

        return response()->download($fullPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}
