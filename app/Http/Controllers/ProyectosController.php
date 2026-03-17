<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\OfferProject;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProyectosController extends Controller
{
    public function index(Request $request)
    {
        $company = Company::instance();

        $query = Project::where('company_id', $company->id)
            ->orderBy('fecha_inicio', 'desc');

        // Simple filters
        if ($request->filled('rubro')) {
            $query->where('unspsc_codigo', 'like', $request->rubro.'%');
        }
        if ($request->filled('year')) {
            $query->whereYear('fecha_inicio', $request->year);
        }

        $projects = $query->paginate(20)->withQueryString();

        // Year list for filter dropdown
        $years = Project::where('company_id', $company->id)
            ->whereNotNull('fecha_inicio')
            ->selectRaw('YEAR(fecha_inicio) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view('proyectos.index', compact('projects', 'years'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'cliente' => 'required|string|max:255',
        ]);

        $company = Company::instance();

        $project = Project::create(array_merge($data, [
            'company_id' => $company->id,
            'currency' => 'DOP',
        ]));

        return redirect()->route('proyectos.show', $project)->with('success', 'Proyecto creado. Completa los detalles.');
    }

    public function show(Project $proyecto)
    {
        $proyecto->load('documents');

        return view('proyectos.show', compact('proyecto'));
    }

    public function update(Request $request, Project $proyecto)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'cliente' => 'required|string|max:255',
            'numero_contrato' => 'nullable|string|max:100',
            'monto' => 'nullable|numeric|min:0',
            'currency' => 'required|in:DOP,USD',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'descripcion' => 'nullable|string|max:3000',
            'unspsc_codigo' => 'nullable|string|max:20',
            'contacto_cliente' => 'nullable|string|max:255',
            'contacto_telefono' => 'nullable|string|max:30',
        ]);

        $proyecto->update($data);

        return redirect()->route('proyectos.show', $proyecto)->with('success', 'Proyecto actualizado.');
    }

    public function destroy(Project $proyecto)
    {
        $activeRef = OfferProject::where('project_id', $proyecto->id)
            ->whereHas('offer', fn ($q) => $q->whereIn('estado', ['borrador', 'en_preparacion', 'listo']))
            ->with('offer:id,proceso_nombre')
            ->first();

        if ($activeRef) {
            return back()->with('error', "Este registro está en uso por la oferta \"{$activeRef->offer->proceso_nombre}\". Desactívalo en lugar de eliminarlo.");
        }

        // Delete all supporting documents from disk
        foreach ($proyecto->documents as $doc) {
            Storage::disk('vault')->delete($doc->path);
        }
        $proyecto->delete();

        return redirect()->route('proyectos.index')->with('success', 'Proyecto eliminado.');
    }

    // Supporting documents
    public function storeDocument(Request $request, Project $proyecto)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'file' => 'required|file|max:20480',
        ]);

        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();
        $stored = $file->storeAs(
            "vault/{$proyecto->company_id}/project_docs/{$proyecto->id}",
            Str::uuid().'.'.$ext,
            'vault'
        );

        ProjectDocument::create([
            'project_id' => $proyecto->id,
            'nombre' => $request->nombre,
            'filename' => $file->getClientOriginalName(),
            'path' => $stored,
        ]);

        return redirect()->route('proyectos.show', $proyecto)->with('success', 'Documento agregado.');
    }

    public function downloadDocument(Project $proyecto, ProjectDocument $documento)
    {
        abort_unless($documento->project_id === $proyecto->id, 403);
        abort_unless(Storage::disk('vault')->exists($documento->path), 404);

        return Storage::disk('vault')->download($documento->path, $documento->filename);
    }

    public function destroyDocument(Project $proyecto, ProjectDocument $documento)
    {
        abort_unless($documento->project_id === $proyecto->id, 403);
        Storage::disk('vault')->delete($documento->path);
        $documento->delete();

        return redirect()->route('proyectos.show', $proyecto)->with('success', 'Documento eliminado.');
    }
}
