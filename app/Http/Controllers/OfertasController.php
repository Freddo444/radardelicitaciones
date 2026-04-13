<?php

namespace App\Http\Controllers;

use App\Exceptions\TrialLimitExceededException;
use App\Jobs\ParsePliegoJob;
use App\Models\Bid;
use App\Models\BidDocument;
use App\Models\Equipment;
use App\Models\FinancialRecord;
use App\Models\Offer;
use App\Models\OfferEquipment;
use App\Models\OfferEvent;
use App\Models\OfferFinancial;
use App\Models\OfferGeneratedFile;
use App\Models\OfferParseAttempt;
use App\Models\OfferPersonnel;
use App\Models\OfferProject;
use App\Models\OfferRequirement;
use App\Models\OfferRequirementItem;
use App\Models\OfferSnapshot;
use App\Models\Personnel;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\VaultDocument;
use App\Services\DgcpApiClient;
use App\Services\FormGeneratorService;
use App\Services\GeminiService;
use App\Services\OfferAssemblyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OfertasController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────

    public function index()
    {
        $company = currentCompany();
        $offers = Offer::where('company_id', $company->id)
            ->orderByRaw("FIELD(estado, 'en_preparacion', 'borrador', 'listo', 'enviado')")
            ->orderBy('fecha_limite')
            ->paginate(20);

        return view('ofertas.index', compact('offers'));
    }

    // ── Create / Store ────────────────────────────────────────────────

    public function create(Request $request)
    {
        $bid = $request->filled('bid') ? Bid::find($request->bid) : null;

        return view('ofertas.create', compact('bid'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bid_id' => 'nullable|integer|exists:bids,id',
            'proceso_codigo' => 'nullable|string|max:100',
            'proceso_nombre' => 'required|string|max:500',
            'entidad_nombre' => 'nullable|string|max:255',
            'fecha_limite' => 'nullable|date',
            'notas' => 'nullable|string|max:2000',
        ]);

        $company = currentCompany();

        // If linking to a bid, pull denormalized info
        if (! empty($data['bid_id'])) {
            $bid = Bid::find($data['bid_id']);
            $data['proceso_codigo'] ??= $bid->process_code;
            $data['proceso_nombre'] ??= $bid->title;
            $data['entidad_nombre'] ??= $bid->buyer_name;
            $data['fecha_limite'] ??= $bid->tender_deadline;
        }

        $offer = Offer::create(array_merge($data, ['company_id' => $company->id]));

        return redirect()->route('ofertas.show', $offer)->with('success', 'Oferta creada.');
    }

    // ── Show (main workspace) ─────────────────────────────────────────

    public function show(Offer $oferta)
    {
        $oferta->load([
            'parseAttempts.bidDocument',
            'activeRequirements.items',
            'personnel.person',
            'projects.project',
            'equipment.equipment',
            'financials.financialRecord',
            'snapshots',
            'events',
            'generatedFiles',
        ]);

        $company = currentCompany();
        $tab = request('tab', 'pliego');

        // Available vault records for composition pickers
        $availablePersonnel = Personnel::where('company_id', $company->id)->active()->orderBy('nombre')->get();
        $availableProjects = Project::where('company_id', $company->id)->orderByDesc('fecha_inicio')->get();
        $availableEquipment = Equipment::where('company_id', $company->id)->active()->orderBy('descripcion')->get();
        $availableFinancials = FinancialRecord::where('company_id', $company->id)->orderByDesc('anio_fiscal')->get();

        $activeParse = $oferta->activeParse();

        return view('ofertas.show', compact(
            'oferta', 'tab', 'activeParse',
            'availablePersonnel', 'availableProjects', 'availableEquipment', 'availableFinancials'
        ));
    }

    // ── Delete ────────────────────────────────────────────────────────

    public function destroy(Offer $oferta)
    {
        abort_unless(in_array($oferta->estado, ['borrador', 'en_preparacion']), 403, 'Solo se puede eliminar una oferta en borrador o en preparación.');
        $oferta->delete();

        return redirect()->route('ofertas.index')->with('success', 'Oferta eliminada.');
    }

    // ── State transitions ─────────────────────────────────────────────

    public function markListo(Offer $oferta)
    {
        abort_unless($oferta->canMarkListo(), 422, 'No cumple las condiciones para marcar como Listo.');
        $oferta->update(['estado' => 'listo']);

        return back()->with('success', 'Oferta marcada como Lista.');
    }

    public function markEnviado(Offer $oferta)
    {
        abort_unless($oferta->estado === 'listo', 422, 'La oferta debe estar en estado Listo.');
        abort_unless($oferta->snapshots()->exists(), 422, 'Debes ensamblar la oferta antes de marcarla como Enviada.');
        $oferta->update(['estado' => 'enviado', 'enviado_at' => now()]);

        return back()->with('success', 'Oferta marcada como Enviada.');
    }

    public function reabrir(Offer $oferta)
    {
        abort_unless($oferta->estado === 'enviado', 422);
        $oferta->update(['estado' => 'en_preparacion', 'enviado_at' => null]);

        return back()->with('success', 'Oferta reabierta.');
    }

    // ── Parse ─────────────────────────────────────────────────────────

    public function uploadPliego(Request $request, Offer $oferta, GeminiService $gemini)
    {
        abort_unless($oferta->isEditable(), 403, 'La oferta está bloqueada.');

        $request->validate([
            'pliego' => 'required|file|mimes:pdf|max:51200',
        ]);

        $file = $request->file('pliego');
        $content = file_get_contents($file->getRealPath());
        $path = "bid_docs/{$oferta->company_id}/{$oferta->id}/".$file->getClientOriginalName();

        Storage::disk('bid_docs')->put($path, $content);
        $sha256 = hash('sha256', $content);

        $bidDoc = BidDocument::create([
            'offer_id' => $oferta->id,
            'document_type' => 'pliego',
            'original_filename' => $file->getClientOriginalName(),
            'downloaded_at' => now(),
            'sha256' => $sha256,
            'local_path' => $path,
            'file_size_bytes' => strlen($content),
        ]);

        try {
            $gemini->reparse($oferta, $bidDoc);
        } catch (TrialLimitExceededException $e) {
            return back()->with('error', 'Límite de uso alcanzado para su plan actual. Actualice su suscripción para continuar.');
        }

        if ($oferta->estado === 'borrador') {
            $oferta->update(['estado' => 'en_preparacion']);
        }

        return back()->with('success', 'Pliego subido y análisis iniciado.');
    }

    public function triggerParse(Request $request, Offer $oferta, GeminiService $gemini)
    {
        abort_unless($oferta->isEditable(), 403);

        $doc = $oferta->bidDocuments()->latest()->first()
            ?? BidDocument::where('offer_id', $oferta->id)->latest()->first();

        abort_unless($doc, 422, 'No hay pliego disponible. Súbelo primero.');

        if ($oferta->activeParse()?->status === 'verified') {
            // Confirmed re-parse — clear verification
            $oferta->activeParse()?->update(['human_verified_at' => null, 'human_verified_by' => null]);
        }

        try {
            $gemini->reparse($oferta, $doc);
        } catch (TrialLimitExceededException $e) {
            return back()->with('error', 'Límite de uso alcanzado para su plan actual. Actualice su suscripción para continuar.');
        }

        return back()->with('success', 'Re-análisis iniciado.');
    }

    public function verifyParse(Request $request, Offer $oferta, OfferParseAttempt $attempt, GeminiService $gemini)
    {
        abort_unless($attempt->offer_id === $oferta->id, 403);
        abort_unless(in_array($attempt->status, ['parsed', 'needs_review']), 422);

        $gemini->verify($attempt);

        // Drop listo → en_preparacion if user edited after marking listo (requirements not changed here, just verifying)
        return back()->with('success', 'Extracción verificada.');
    }

    /**
     * Fetch documents from the DGCP API for this offer's process code.
     */
    public function apiDocuments(Offer $oferta, DgcpApiClient $api)
    {
        if (! $oferta->proceso_codigo) {
            return response()->json(['docs' => []]);
        }

        try {
            $docs = $api->fetchDocuments($oferta->proceso_codigo);
        } catch (\Throwable) {
            $docs = [];
        }

        return response()->json(['docs' => $docs]);
    }

    /**
     * Download a document from the DGCP API and send it to Gemini for analysis.
     */
    public function parseFromApi(Request $request, Offer $oferta)
    {
        abort_unless($oferta->isEditable(), 403, 'La oferta está bloqueada.');

        $request->validate([
            'url' => 'required|url',
            'filename' => 'required|string|max:255',
        ]);

        // Pre-check trial limit before dispatching async job
        $subscription = Subscription::where('user_id', $oferta->company->owner_id)->first();
        if ($subscription && $subscription->status === 'trialing' && ! $subscription->canUseTrial()) {
            return response()->json(['error' => (new TrialLimitExceededException)->getMessage()], 422);
        }

        // Create attempt now so it's visible immediately (before queue picks up the job)
        $attempt = OfferParseAttempt::create([
            'offer_id' => $oferta->id,
            'status' => 'pending',
            'parser_version' => 'v1.0',
            'triggered_by' => Auth::id(),
        ]);

        ParsePliegoJob::dispatch($oferta, $request->url, $request->filename, $attempt->id);

        return response()->json(['ok' => true]);
    }

    public function parseStatus(Offer $oferta)
    {
        $attempt = $oferta->parseAttempts()->latest()->first();

        if (! $attempt) {
            return response()->json(['status' => 'none']);
        }

        // Detect stale running/pending attempts (job crashed or timed out)
        if ($attempt->isPending() && $attempt->created_at->diffInMinutes(now()) > 4) {
            $attempt->update(['status' => 'failed', 'failure_reason' => 'El análisis excedió el tiempo límite. Intenta de nuevo.']);
        }

        return response()->json([
            'status' => $attempt->status,
            'failure_reason' => $attempt->failure_reason,
            'confidence_score' => $attempt->confidence_score,
        ]);
    }

    // ── Requirements ──────────────────────────────────────────────────

    public function storeRequirement(Request $request, Offer $oferta)
    {
        abort_unless($oferta->isEditable(), 403);
        $this->invalidateVerification($oferta);

        $data = $request->validate([
            'descripcion' => 'required|string|max:500',
            'tipo' => 'required|in:documento,financiero,personal,equipo,experiencia,formato,otro',
            'notes' => 'nullable|string|max:1000',
        ]);

        $oferta->requirements()->create(array_merge($data, ['source' => 'manual']));

        return back()->with('success', 'Requisito agregado.');
    }

    public function updateRequirement(Request $request, Offer $oferta, OfferRequirement $req)
    {
        abort_unless($req->offer_id === $oferta->id, 403);
        abort_unless($oferta->isEditable(), 403);
        $this->invalidateVerification($oferta);

        $data = $request->validate([
            'descripcion' => 'required|string|max:500',
            'tipo' => 'required|in:documento,financiero,personal,equipo,experiencia,formato,otro',
            'estado' => 'required|in:PENDIENTE,CUMPLE,NO_CUMPLE,ACEPTADO',
            'notes' => 'nullable|string|max:1000',
            'acceptance_reason' => 'nullable|string|max:500',
        ]);

        $req->update($data);

        return back()->with('success', 'Requisito actualizado.');
    }

    public function destroyRequirement(Offer $oferta, OfferRequirement $req)
    {
        abort_unless($req->offer_id === $oferta->id, 403);
        abort_unless($oferta->isEditable(), 403);
        $this->invalidateVerification($oferta);

        $req->delete();

        return back()->with('success', 'Requisito eliminado.');
    }

    public function storeRequirementItem(Request $request, Offer $oferta, OfferRequirement $req)
    {
        abort_unless($req->offer_id === $oferta->id, 403);
        abort_unless($oferta->isEditable(), 403);

        if ($request->input('vault_ref_type') === 'uploaded_file') {
            $request->validate([
                'upload_name' => 'required|string|max:255',
                'upload_file' => 'required|file|max:20480',
                'upload_category' => 'required|in:'.implode(',', array_keys(VaultDocument::$categories)),
                'role_note' => 'nullable|string|max:255',
            ]);

            $company = currentCompany();
            $file = $request->file('upload_file');
            $category = $request->input('upload_category');
            $stored = $file->storeAs(
                "vault/{$company->id}/{$category}",
                Str::uuid().'.'.$file->getClientOriginalExtension(),
                'vault'
            );

            $doc = VaultDocument::create([
                'company_id' => $company->id,
                'category' => $category,
                'name' => $request->input('upload_name'),
                'filename' => $file->getClientOriginalName(),
                'path' => $stored,
                'copy_type' => 'original',
                'language' => 'es',
                'is_current' => true,
            ]);

            $data = [
                'vault_ref_type' => 'vault_documents',
                'vault_ref_id' => $doc->id,
                'role_note' => $request->input('role_note'),
            ];
        } else {
            $data = $request->validate([
                'vault_ref_type' => 'required|in:vault_documents,personnel,projects,equipment,financial_records,offer_generated_files',
                'vault_ref_id' => 'required|integer',
                'role_note' => 'nullable|string|max:255',
            ]);
        }

        $req->items()->create($data);
        $req->recalculateEstado();
        $this->invalidateVerification($oferta);

        return back()->with('success', 'Documento asignado al requisito.');
    }

    public function destroyRequirementItem(Offer $oferta, OfferRequirement $req, OfferRequirementItem $item)
    {
        abort_unless($req->offer_id === $oferta->id && $item->offer_requirement_id === $req->id, 403);
        abort_unless($oferta->isEditable(), 403);

        $item->delete();
        $req->recalculateEstado();
        $this->invalidateVerification($oferta);

        return back()->with('success', 'Asignación eliminada.');
    }

    // ── Composition ───────────────────────────────────────────────────

    public function addPersonnel(Request $request, Offer $oferta)
    {
        abort_unless($oferta->isEditable(), 403);
        $data = $request->validate(['personnel_id' => 'required|integer|exists:personnel,id', 'role_note' => 'nullable|string|max:255']);
        $oferta->personnel()->firstOrCreate(['personnel_id' => $data['personnel_id']], ['role_note' => $data['role_note'] ?? null]);

        return back()->with('success', 'Personal agregado a la oferta.');
    }

    public function removePersonnel(Offer $oferta, OfferPersonnel $op)
    {
        abort_unless($op->offer_id === $oferta->id && $oferta->isEditable(), 403);
        $op->delete();

        return back()->with('success', 'Personal removido.');
    }

    public function addProject(Request $request, Offer $oferta)
    {
        abort_unless($oferta->isEditable(), 403);
        $data = $request->validate(['project_id' => 'required|integer|exists:projects,id', 'role_note' => 'nullable|string|max:255']);
        $oferta->projects()->firstOrCreate(['project_id' => $data['project_id']], ['role_note' => $data['role_note'] ?? null]);

        return back()->with('success', 'Proyecto agregado.');
    }

    public function removeProject(Offer $oferta, OfferProject $op)
    {
        abort_unless($op->offer_id === $oferta->id && $oferta->isEditable(), 403);
        $op->delete();

        return back()->with('success', 'Proyecto removido.');
    }

    public function addEquipment(Request $request, Offer $oferta)
    {
        abort_unless($oferta->isEditable(), 403);
        $data = $request->validate(['equipment_id' => 'required|integer|exists:equipment,id', 'role_note' => 'nullable|string|max:255']);
        $oferta->equipment()->firstOrCreate(['equipment_id' => $data['equipment_id']], ['role_note' => $data['role_note'] ?? null]);

        return back()->with('success', 'Equipo agregado.');
    }

    public function removeEquipment(Offer $oferta, OfferEquipment $oe)
    {
        abort_unless($oe->offer_id === $oferta->id && $oferta->isEditable(), 403);
        $oe->delete();

        return back()->with('success', 'Equipo removido.');
    }

    public function addFinancial(Request $request, Offer $oferta)
    {
        abort_unless($oferta->isEditable(), 403);
        $data = $request->validate(['financial_record_id' => 'required|integer|exists:financial_records,id', 'role_note' => 'nullable|string|max:255']);
        $oferta->financials()->firstOrCreate(['financial_record_id' => $data['financial_record_id']], ['role_note' => $data['role_note'] ?? null]);

        return back()->with('success', 'Año fiscal agregado.');
    }

    public function removeFinancial(Offer $oferta, OfferFinancial $of)
    {
        abort_unless($of->offer_id === $oferta->id && $oferta->isEditable(), 403);
        $of->delete();

        return back()->with('success', 'Año fiscal removido.');
    }

    // ── Events / Timeline ─────────────────────────────────────────────

    public function storeEvent(Request $request, Offer $oferta)
    {
        abort_unless($oferta->isEditable(), 403);
        $data = $request->validate([
            'event_type' => 'required|in:visita_campo,aclaraciones_deadline,entrega_oferta,apertura_sobres,adjudicacion_estimada,custom',
            'description' => 'nullable|string|max:255',
            'event_date' => 'required|date',
            'alert_days_before' => 'required|integer|min:0|max:30',
        ]);
        $oferta->events()->create($data);

        return back()->with('success', 'Evento agregado.');
    }

    public function updateEvent(Request $request, Offer $oferta, OfferEvent $event)
    {
        abort_unless($event->offer_id === $oferta->id && $oferta->isEditable(), 403);
        $data = $request->validate([
            'description' => 'nullable|string|max:255',
            'event_date' => 'required|date',
            'alert_days_before' => 'required|integer|min:0|max:30',
            'status' => 'required|in:pending,completed,missed',
        ]);
        $event->update($data);

        return back()->with('success', 'Evento actualizado.');
    }

    public function destroyEvent(Offer $oferta, OfferEvent $event)
    {
        abort_unless($event->offer_id === $oferta->id && $oferta->isEditable(), 403);
        $event->delete();

        return back()->with('success', 'Evento eliminado.');
    }

    // ── Assembly ──────────────────────────────────────────────────────

    public function assemble(Offer $oferta, OfferAssemblyService $assembly)
    {
        abort_unless($oferta->estado === 'listo', 422, 'La oferta debe estar en estado Listo para ensamblar.');

        $snapshot = $assembly->assemble($oferta);

        return redirect()->route('ofertas.show', [$oferta, 'tab' => 'ensamblar'])
            ->with('success', 'Paquete ensamblado correctamente.');
    }

    public function downloadSnapshot(Offer $oferta, OfferSnapshot $snapshot)
    {
        abort_unless($snapshot->offer_id === $oferta->id, 403);
        abort_unless($snapshot->zip_path, 404);

        $fullPath = storage_path('app/'.$snapshot->zip_path);
        abort_unless(file_exists($fullPath), 404);

        return response()->download($fullPath, basename($fullPath));
    }

    public function saveSobres(Request $request, Offer $oferta)
    {
        abort_unless(in_array($oferta->estado, ['en_preparacion', 'listo']), 403);

        $data = $request->validate([
            'sobres' => 'required|array',
            'sobres.*' => 'nullable|in:A,B',
        ]);

        foreach ($data['sobres'] as $reqId => $sobre) {
            OfferRequirement::where('id', $reqId)
                ->where('offer_id', $oferta->id)
                ->update(['sobre' => $sobre ?: null]);
        }

        return redirect()->route('ofertas.show', [$oferta, 'tab' => 'ensamblar'])
            ->with('success', 'Asignación de sobres guardada.');
    }

    public function generateSobres(Offer $oferta, OfferAssemblyService $assembly)
    {
        abort_unless(in_array($oferta->estado, ['listo', 'en_preparacion']), 422);

        $result = $assembly->assembleSobres($oferta);

        if (empty($result)) {
            return redirect()->route('ofertas.show', [$oferta, 'tab' => 'ensamblar'])
                ->with('error', 'No hay requisitos asignados a ningún sobre.');
        }

        return redirect()->route('ofertas.show', [$oferta, 'tab' => 'ensamblar'])
            ->with('success', 'Sobres generados correctamente.');
    }

    public function downloadSobre(Offer $oferta, string $sobre)
    {
        abort_unless(in_array($sobre, ['A', 'B']), 404);

        $pattern = storage_path("app/generated/sobres/Sobre {$sobre}-{$oferta->proceso_codigo}*.zip");
        $files = glob($pattern);

        if (empty($files)) {
            abort(404, "Sobre {$sobre} no ha sido generado.");
        }

        $latest = collect($files)->sort()->last();

        return response()->download($latest, basename($latest));
    }

    // ── Form generation within offer context ──────────────────────────

    public function generateForm(Request $request, Offer $oferta, FormGeneratorService $generator)
    {
        abort_unless($oferta->isEditable(), 403, 'No se pueden generar formularios en este estado.');

        $request->validate([
            'form_code' => 'required|string',
            'personnel_id' => 'nullable|integer',
            'project_ids' => 'nullable|array',
            'cargo_propuesto' => 'nullable|string|max:255',
            'proceso_ref' => 'nullable|string|max:100',
            'entidad_nombre' => 'nullable|string|max:255',
        ]);

        $params = array_filter($request->only([
            'personnel_id', 'project_ids', 'cargo_propuesto',
            'proceso_ref', 'proceso_nombre', 'entidad_nombre',
        ]), fn ($v) => $v !== null && $v !== '');

        // Pre-fill from offer context
        $params['proceso_ref'] ??= $oferta->proceso_codigo;
        $params['proceso_nombre'] ??= $oferta->proceso_nombre;
        $params['entidad_nombre'] ??= $oferta->entidad_nombre;

        // Find existing file for this form+offer (for supersedes chain)
        $existing = OfferGeneratedFile::where('offer_id', $oferta->id)
            ->where('form_code', $request->form_code)
            ->latest('generated_at')
            ->first();

        $file = $generator->generate($request->form_code, $params, $oferta->id, $existing?->id);

        return back()->with('success', 'Formulario generado: '.OfferGeneratedFile::$forms[$file->form_code] ?? $file->form_code)
            ->with('download_id', $file->id);
    }

    public function downloadGeneratedFile(Offer $oferta, OfferGeneratedFile $file)
    {
        abort_unless($file->offer_id === $oferta->id, 403);
        $fullPath = storage_path('app/'.$file->path);
        abort_unless(file_exists($fullPath), 404);

        $slug = str_replace(['.', ' '], '-', strtolower($file->form_code));
        $filename = $slug.'_'.$file->generated_at->format('Ymd').'.docx';

        return response()->download($fullPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public function viewGeneratedFile(Offer $oferta, OfferGeneratedFile $file)
    {
        abort_unless($file->offer_id === $oferta->id, 403);
        $docxPath = storage_path('app/'.$file->path);
        abort_unless(file_exists($docxPath), 404);

        $pdfDir = storage_path('app/generated/pdf_cache');
        if (! is_dir($pdfDir)) {
            mkdir($pdfDir, 0755, true);
        }

        $pdfPath = $pdfDir.'/'.pathinfo($file->path, PATHINFO_FILENAME).'.pdf';

        // Convert if PDF doesn't exist or is older than the docx
        if (! file_exists($pdfPath) || filemtime($pdfPath) < filemtime($docxPath)) {
            $cmd = sprintf(
                'HOME=/var/www soffice --headless --convert-to pdf --outdir %s %s 2>&1',
                escapeshellarg($pdfDir),
                escapeshellarg($docxPath)
            );
            exec($cmd, $output, $exitCode);
            if ($exitCode !== 0 || ! file_exists($pdfPath)) {
                Log::error('LibreOffice conversion failed', ['cmd' => $cmd, 'exit' => $exitCode, 'output' => $output]);
                abort(500, 'Error al convertir a PDF: '.implode("\n", $output));
            }
        }

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline',
        ]);
    }

    public function deleteGeneratedFile(Offer $oferta, OfferGeneratedFile $file)
    {
        abort_unless($file->offer_id === $oferta->id, 403);

        // Remove checklist items referencing this file
        OfferRequirementItem::where('vault_ref_type', 'offer_generated_files')
            ->where('vault_ref_id', $file->id)
            ->delete();

        $fullPath = storage_path('app/'.$file->path);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $file->delete();

        return back()->with('success', 'Formulario eliminado.');
    }

    // ── Private helpers ───────────────────────────────────────────────

    /**
     * When requirements are edited, clear human verification and drop listo → en_preparacion.
     */
    private function invalidateVerification(Offer $oferta): void
    {
        $activeParse = $oferta->activeParse();
        if ($activeParse?->isVerified()) {
            $activeParse->update(['human_verified_at' => null, 'human_verified_by' => null, 'status' => 'parsed']);
        }
        if ($oferta->estado === 'listo') {
            $oferta->update(['estado' => 'en_preparacion']);
        }
    }
}
