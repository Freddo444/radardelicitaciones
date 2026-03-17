<?php

namespace App\Services;

use App\Models\BidDocument;
use App\Models\Offer;
use App\Models\OfferParseAttempt;
use App\Models\OfferRequirement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeminiService
{
    private string $apiKey;

    private string $model = 'gemini-2.0-flash';

    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    private string $parserVersion = 'v1.0';

    private string $prompt = <<<'PROMPT'
Eres un experto en contrataciones públicas de la República Dominicana.
Analiza este pliego de condiciones y extrae la siguiente información en JSON estrictamente válido (sin texto adicional, sin markdown):
{
  "documentos_requeridos": [{"nombre": "", "copias": 0, "tipo": "original|copia|apostilla"}],
  "indices_financieros": {"solvencia_min": null, "liquidez_min": null, "endeudamiento_max": null},
  "personal_requerido": [{"cargo": "", "experiencia_años": 0, "certificaciones": []}],
  "equipos_requeridos": [{"descripcion": "", "cantidad": 0}],
  "experiencia_requerida": {"proyectos_similares": 0, "monto_minimo": 0, "currency": "DOP"},
  "formato_oferta": {"copias": 0, "idioma": "español", "formato": ""},
  "fechas_clave": {"visita_campo": null, "aclaraciones": null, "entrega_oferta": null, "apertura_sobres": null},
  "criterios_evaluacion": [{"criterio": "", "peso": 0}],
  "confidence_score": 0,
  "notas": ""
}
El campo confidence_score (0-100) refleja qué tan completo y claro es el pliego para extraer estos datos.
PROMPT;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', '');
    }

    /**
     * Fetch pliego PDF from DGCP URL, store locally, create parse attempt, and trigger parse.
     * Returns the OfferParseAttempt record.
     */
    public function fetchAndParse(Offer $offer, string $pdfUrl, string $originalFilename): OfferParseAttempt
    {
        // Download PDF
        $attempt = OfferParseAttempt::create([
            'offer_id' => $offer->id,
            'status' => 'pending',
            'parser_version' => $this->parserVersion,
            'triggered_by' => Auth::id(),
        ]);

        try {
            $pdfContent = $this->downloadPdf($pdfUrl);
        } catch (\Exception $e) {
            $attempt->update(['status' => 'failed', 'failure_reason' => 'No se pudo descargar el PDF: '.$e->getMessage()]);
            $this->advanceOfferState($offer, $attempt);

            return $attempt;
        }

        // Store locally
        $localPath = "bid_docs/{$offer->company_id}/{$offer->id}/".$originalFilename;
        Storage::disk('bid_docs')->put($localPath, $pdfContent);
        $sha256 = hash('sha256', $pdfContent);

        $bidDoc = BidDocument::create([
            'offer_id' => $offer->id,
            'document_type' => 'pliego',
            'original_filename' => $originalFilename,
            'source_url' => $pdfUrl,
            'downloaded_at' => now(),
            'sha256' => $sha256,
            'local_path' => $localPath,
            'file_size_bytes' => strlen($pdfContent),
        ]);

        $attempt->update(['bid_document_id' => $bidDoc->id, 'status' => 'running']);

        // Parse with Gemini
        return $this->parseDocument($attempt, $bidDoc, $pdfContent);
    }

    /**
     * Re-parse an existing BidDocument — creates a new attempt row.
     */
    public function reparse(Offer $offer, BidDocument $bidDoc): OfferParseAttempt
    {
        $attempt = OfferParseAttempt::create([
            'offer_id' => $offer->id,
            'bid_document_id' => $bidDoc->id,
            'status' => 'running',
            'parser_version' => $this->parserVersion,
            'triggered_by' => Auth::id(),
        ]);

        $pdfContent = Storage::disk('bid_docs')->get($bidDoc->local_path);

        return $this->parseDocument($attempt, $bidDoc, $pdfContent);
    }

    /**
     * Mark a parse attempt as verified by the current user.
     * Clears verification if offer was listo (drops it back to en_preparacion).
     */
    public function verify(OfferParseAttempt $attempt): void
    {
        $attempt->update([
            'status' => 'verified',
            'human_verified_at' => now(),
            'human_verified_by' => Auth::id(),
        ]);
    }

    // ── Private ───────────────────────────────────────────────────────

    private function parseDocument(OfferParseAttempt $attempt, BidDocument $bidDoc, string $pdfContent): OfferParseAttempt
    {
        if (! $this->apiKey) {
            $attempt->update([
                'status' => 'failed',
                'failure_reason' => 'GEMINI_API_KEY no configurado. Configúralo en Configuración.',
            ]);
            $this->advanceOfferState($attempt->offer, $attempt);

            return $attempt;
        }

        try {
            $base64Pdf = base64_encode($pdfContent);

            $response = Http::timeout(120)
                ->post("{$this->apiUrl}/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents' => [[
                        'parts' => [
                            ['text' => $this->prompt],
                            ['inline_data' => ['mime_type' => 'application/pdf', 'data' => $base64Pdf]],
                        ],
                    ]],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                        'temperature' => 0.1,
                    ],
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException('Gemini HTTP '.$response->status().': '.$response->body());
            }

            $rawBody = $response->body();
            $attempt->update(['raw_extraction' => $rawBody]);

            $parsed = $this->extractJson($rawBody);

            return $this->processExtraction($attempt, $parsed);

        } catch (\Exception $e) {
            Log::error('Gemini parse failed', ['offer_id' => $attempt->offer_id, 'error' => $e->getMessage()]);
            $attempt->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
            $this->advanceOfferState($attempt->offer, $attempt);

            return $attempt;
        }
    }

    private function extractJson(string $rawBody): ?array
    {
        // Try direct JSON from response_mime_type=application/json path
        $decoded = json_decode($rawBody, true);
        if ($decoded && isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $decoded['candidates'][0]['content']['parts'][0]['text'];
            $parsed = json_decode($text, true);
            if ($parsed) {
                return $parsed;
            }
        }

        // Fallback: regex-extract first JSON object from response
        if (preg_match('/\{.*\}/s', $rawBody, $matches)) {
            $parsed = json_decode($matches[0], true);
            if ($parsed) {
                return $parsed;
            }
        }

        return null;
    }

    private function processExtraction(OfferParseAttempt $attempt, ?array $parsed): OfferParseAttempt
    {
        if (! $parsed) {
            $attempt->update(['status' => 'failed', 'failure_reason' => 'No se pudo extraer JSON válido de la respuesta de Gemini.']);
            $this->advanceOfferState($attempt->offer, $attempt);

            return $attempt;
        }

        $confidence = (int) ($parsed['confidence_score'] ?? 0);

        // Check for required fields
        $requiredFields = ['documentos_requeridos', 'indices_financieros', 'personal_requerido'];
        $missingFields = array_filter($requiredFields, fn ($f) => empty($parsed[$f]));

        $status = ($confidence < 60 || count($missingFields) > 0) ? 'needs_review' : 'parsed';

        $attempt->update([
            'status' => $status,
            'confidence_score' => $confidence,
            'parsed_json' => $parsed,
        ]);

        // Populate offer_requirements from parsed data
        $this->populateRequirements($attempt, $parsed);
        $this->populateEvents($attempt, $parsed);

        $this->advanceOfferState($attempt->offer, $attempt);

        return $attempt->fresh();
    }

    private function populateRequirements(OfferParseAttempt $attempt, array $parsed): void
    {
        $offer = $attempt->offer;

        // Mark any existing gemini requirements as superseded
        $offer->requirements()->where('source', 'gemini')->update(['superseded' => true]);

        $reqs = [];

        foreach ($parsed['documentos_requeridos'] ?? [] as $doc) {
            $reqs[] = [
                'offer_id' => $offer->id,
                'parse_attempt_id' => $attempt->id,
                'descripcion' => $doc['nombre'] ?? 'Documento requerido',
                'tipo' => 'documento',
                'source' => 'gemini',
                'notes' => isset($doc['copias']) ? "Copias: {$doc['copias']} ({$doc['tipo']})" : null,
            ];
        }

        foreach ($parsed['personal_requerido'] ?? [] as $p) {
            $reqs[] = [
                'offer_id' => $offer->id,
                'parse_attempt_id' => $attempt->id,
                'descripcion' => $p['cargo'] ?? 'Personal requerido',
                'tipo' => 'personal',
                'source' => 'gemini',
                'notes' => isset($p['experiencia_años']) ? "Experiencia mínima: {$p['experiencia_años']} años" : null,
            ];
        }

        foreach ($parsed['equipos_requeridos'] ?? [] as $eq) {
            $reqs[] = [
                'offer_id' => $offer->id,
                'parse_attempt_id' => $attempt->id,
                'descripcion' => $eq['descripcion'] ?? 'Equipo requerido',
                'tipo' => 'equipo',
                'source' => 'gemini',
                'notes' => isset($eq['cantidad']) ? "Cantidad: {$eq['cantidad']}" : null,
            ];
        }

        $fi = $parsed['indices_financieros'] ?? [];
        if (! empty(array_filter($fi))) {
            $parts = [];
            if (! empty($fi['solvencia_min'])) {
                $parts[] = "Solvencia ≥ {$fi['solvencia_min']}";
            }
            if (! empty($fi['liquidez_min'])) {
                $parts[] = "Liquidez ≥ {$fi['liquidez_min']}";
            }
            if (! empty($fi['endeudamiento_max'])) {
                $parts[] = "Endeudamiento ≤ {$fi['endeudamiento_max']}";
            }
            $reqs[] = [
                'offer_id' => $offer->id,
                'parse_attempt_id' => $attempt->id,
                'descripcion' => 'Índices financieros requeridos',
                'tipo' => 'financiero',
                'source' => 'gemini',
                'notes' => implode(' | ', $parts),
            ];
        }

        $er = $parsed['experiencia_requerida'] ?? [];
        if (! empty($er['proyectos_similares'])) {
            $reqs[] = [
                'offer_id' => $offer->id,
                'parse_attempt_id' => $attempt->id,
                'descripcion' => "Experiencia: {$er['proyectos_similares']} proyecto(s) similar(es)",
                'tipo' => 'experiencia',
                'source' => 'gemini',
                'notes' => ! empty($er['monto_minimo']) ? "Monto mínimo: {$er['currency']} {$er['monto_minimo']}" : null,
            ];
        }

        if (! empty($reqs)) {
            $now = now();
            foreach ($reqs as &$r) {
                $r['estado'] = 'PENDIENTE';
                $r['superseded'] = false;
                $r['created_at'] = $now;
                $r['updated_at'] = $now;
            }
            OfferRequirement::insert($reqs);
        }
    }

    private function populateEvents(OfferParseAttempt $attempt, array $parsed): void
    {
        $offer = $attempt->offer;
        $fechas = $parsed['fechas_clave'] ?? [];
        $map = [
            'visita_campo' => 'visita_campo',
            'aclaraciones' => 'aclaraciones_deadline',
            'entrega_oferta' => 'entrega_oferta',
            'apertura_sobres' => 'apertura_sobres',
        ];

        foreach ($map as $key => $eventType) {
            if (! empty($fechas[$key])) {
                try {
                    $date = Carbon::parse($fechas[$key]);
                    $offer->events()->firstOrCreate(
                        ['event_type' => $eventType],
                        [
                            'event_date' => $date,
                            'alert_days_before' => OfferEvent::$defaultAlertDays[$eventType] ?? 1,
                            'description' => OfferEvent::$types[$eventType] ?? $eventType,
                        ]
                    );
                } catch (\Exception) {
                    // unparseable date — skip
                }
            }
        }
    }

    private function advanceOfferState(Offer $offer, OfferParseAttempt $attempt): void
    {
        // Any parse attempt (even failed) moves offer from borrador → en_preparacion
        if ($offer->estado === 'borrador') {
            $offer->update(['estado' => 'en_preparacion']);
        }
    }

    private function downloadPdf(string $url): string
    {
        $response = Http::timeout(60)->get($url);
        if (! $response->successful()) {
            throw new \RuntimeException("HTTP {$response->status()} al descargar PDF");
        }

        return $response->body();
    }
}
