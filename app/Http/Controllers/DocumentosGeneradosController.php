<?php

namespace App\Http\Controllers;

use App\Models\OfferGeneratedFile;
use App\Models\PrellenadoPackage;

class DocumentosGeneradosController extends Controller
{
    public function index()
    {
        $packages = PrellenadoPackage::with(['bid', 'files'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('documentos-generados.index', compact('packages'));
    }

    public function show(PrellenadoPackage $package)
    {
        $package->load(['bid', 'files']);

        return view('documentos-generados.show', compact('package'));
    }

    public function downloadZip(PrellenadoPackage $package)
    {
        if (! $package->zip_path) {
            abort(404, 'No se encontró el archivo ZIP');
        }

        $path = storage_path('app/'.$package->zip_path);
        if (! file_exists($path)) {
            abort(404, 'Archivo no encontrado');
        }

        return response()->download($path);
    }

    public function downloadFile(OfferGeneratedFile $file)
    {
        $path = storage_path('app/'.$file->path);
        if (! file_exists($path)) {
            abort(404, 'Archivo no encontrado');
        }

        return response()->download($path);
    }
}
