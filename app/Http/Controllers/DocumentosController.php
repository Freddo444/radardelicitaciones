<?php

namespace App\Http\Controllers;

use App\Models\VaultDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentosController extends Controller
{
    public function index()
    {
        $company = currentCompany();

        // If no company profile yet, redirect
        if (! $company->exists) {
            return redirect()->route('empresa.index')
                ->with('info', 'Configura el perfil de empresa antes de subir documentos.');
        }

        $documents = VaultDocument::where('company_id', $company->id)
            ->current()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        // Count expiring / expired for banner
        $expiryAlerts = VaultDocument::where('company_id', $company->id)
            ->current()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(30))
            ->count();

        return view('documentos.index', compact('company', 'documents', 'expiryAlerts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:'.implode(',', array_keys(VaultDocument::$categories)),
            'file' => 'required|file|max:20480', // 20 MB
            'issued_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'issuer' => 'nullable|string|max:255',
            'document_number' => 'nullable|string|max:100',
            'signed_by' => 'nullable|string|max:255',
            'notarized' => 'boolean',
            'copy_type' => 'required|in:'.implode(',', array_keys(VaultDocument::$copyTypes)),
            'language' => 'nullable|string|max:10',
            'notes' => 'nullable|string|max:1000',
            'internal_only' => 'boolean',
        ]);

        $company = currentCompany();
        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();
        $stored = $file->storeAs(
            "vault/{$company->id}/{$request->category}",
            Str::uuid().'.'.$ext,
            'vault'
        );

        VaultDocument::create([
            'company_id' => $company->id,
            'category' => $request->category,
            'name' => $request->name,
            'filename' => $file->getClientOriginalName(),
            'path' => $stored,
            'issued_at' => $request->issued_at,
            'expires_at' => $request->expires_at,
            'issuer' => $request->issuer,
            'document_number' => $request->document_number,
            'signed_by' => $request->signed_by,
            'notarized' => $request->boolean('notarized'),
            'copy_type' => $request->copy_type ?? 'original',
            'language' => $request->language ?? 'es',
            'notes' => $request->notes,
            'internal_only' => $request->boolean('internal_only'),
            'is_current' => true,
        ]);

        return redirect()->route('documentos.index')->with('success', 'Documento subido correctamente.');
    }

    public function download(VaultDocument $documento)
    {
        // Controller-gated: auth already enforced by middleware on route
        abort_unless(Storage::disk('vault')->exists($documento->path), 404);

        return Storage::disk('vault')->download($documento->path, $documento->filename);
    }

    public function replace(Request $request, VaultDocument $documento)
    {
        $request->validate([
            'file' => 'required|file|max:20480',
            'name' => 'nullable|string|max:255',
            'issued_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'issuer' => 'nullable|string|max:255',
            'document_number' => 'nullable|string|max:100',
            'signed_by' => 'nullable|string|max:255',
            'notarized' => 'boolean',
            'copy_type' => 'required|in:'.implode(',', array_keys(VaultDocument::$copyTypes)),
            'language' => 'nullable|string|max:10',
            'notes' => 'nullable|string|max:1000',
            'internal_only' => 'boolean',
        ]);

        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();
        $stored = $file->storeAs(
            "vault/{$documento->company_id}/{$documento->category}",
            Str::uuid().'.'.$ext,
            'vault'
        );

        $documento->replaceWith([
            'name' => $request->name ?? $documento->name,
            'filename' => $file->getClientOriginalName(),
            'path' => $stored,
            'issued_at' => $request->issued_at,
            'expires_at' => $request->expires_at,
            'issuer' => $request->issuer,
            'document_number' => $request->document_number,
            'signed_by' => $request->signed_by,
            'notarized' => $request->boolean('notarized'),
            'copy_type' => $request->copy_type ?? $documento->copy_type,
            'language' => $request->language ?? $documento->language,
            'notes' => $request->notes,
            'internal_only' => $request->boolean('internal_only'),
        ]);

        return redirect()->route('documentos.index')->with('success', 'Nueva versión del documento guardada.');
    }

    public function versions(VaultDocument $documento)
    {
        // Find the root of the version chain (the original upload)
        $root = $documento;
        while ($root->replaces_document_id) {
            $parent = VaultDocument::find($root->replaces_document_id);
            if (! $parent) {
                break;
            }
            $root = $parent;
        }

        // Walk forward from root to build the chain newest-first
        $chain = $documento->versionChain();

        return view('documentos.versions', compact('documento', 'chain'));
    }
}
