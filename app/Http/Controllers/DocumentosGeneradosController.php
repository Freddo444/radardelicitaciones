<?php

namespace App\Http\Controllers;

use App\Models\OfferGeneratedFile;
use App\Models\Offer;
use App\Models\PrellenadoPackage;
use Illuminate\Support\Facades\Auth;

class DocumentosGeneradosController extends Controller
{
    public function index()
    {
        $packages = PrellenadoPackage::with(['bid', 'files'])
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('documentos-generados.index', compact('packages'));
    }

    public function show(PrellenadoPackage $package)
    {
        abort_unless($package->user_id === Auth::id(), 403);
        $package->load(['bid', 'files']);

        return view('documentos-generados.show', compact('package'));
    }

    public function downloadZip(PrellenadoPackage $package)
    {
        abort_unless($package->user_id === Auth::id(), 403);
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
        if ($file->prellenado_package_id) {
            $package = PrellenadoPackage::find($file->prellenado_package_id);
            abort_unless($package && $package->user_id === Auth::id(), 403);
        } elseif ($file->offer_id) {
            $offer = Offer::find($file->offer_id);
            abort_unless($offer && $offer->company_id === currentCompany()?->id, 403);
        } else {
            abort(403);
        }

        $path = storage_path('app/'.$file->path);
        if (! file_exists($path)) {
            abort(404, 'Archivo no encontrado');
        }

        return response()->download($path);
    }
}
