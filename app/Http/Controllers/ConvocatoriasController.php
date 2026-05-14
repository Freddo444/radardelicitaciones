<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use App\Models\BidWatch;
use App\Models\CompanyBid;
use App\Services\DgcpApiClient;
use App\Services\PortalScraperService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ConvocatoriasController extends Controller
{
    public function index(Request $request)
    {
        $companyId = currentCompany()->id;
        $query = Bid::forCompany($companyId)->filtered($companyId);

        // Tab filter
        if ($request->input('tab') === 'guardadas') {
            $query->where('company_bid.is_bookmarked', true);
        } elseif ($request->input('tab') === 'recomendadas') {
            $query->relevant();
        }

        // Hide expired bids by default (unless bookmarked tab or explicitly showing all)
        if ($request->input('tab') !== 'guardadas' && ! $request->has('vigentes')) {
            $query->where(function ($q) {
                $q->whereNull('bids.tender_deadline')
                    ->orWhere('bids.tender_deadline', '>=', now());
            });
        }

        // Search
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('bids.title', 'like', "%{$search}%")
                    ->orWhere('bids.buyer_name', 'like', "%{$search}%")
                    ->orWhere('bids.process_code', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->input('estado')) {
            $query->where('bids.status', $status);
        }

        // Method filter
        if ($method = $request->input('modalidad')) {
            $query->where('bids.procurement_method', $method);
        }

        // Only open deadlines
        if ($request->boolean('vigentes')) {
            $query->where(function ($q) {
                $q->whereNull('bids.tender_deadline')
                    ->orWhere('bids.tender_deadline', '>=', now());
            });
        }

        // Sortable columns
        $sortable = ['title', 'buyer_name', 'status', 'amount_estimated', 'tender_deadline', 'published_at'];
        $sort = in_array($request->input('sort'), $sortable) ? $request->input('sort') : 'published_at';
        $dir = $request->input('dir') === 'asc' ? 'asc' : 'desc';

        $bids = $query->orderBy("bids.{$sort}", $dir)->paginate(25)->withQueryString();

        // For filter dropdowns (global — all bids)
        $statuses = Bid::select('status')->distinct()->whereNotNull('status')->orderBy('status')->pluck('status');
        $methods = Bid::select('procurement_method')->distinct()->whereNotNull('procurement_method')->orderBy('procurement_method')->pluck('procurement_method');

        $bookmarkCount = Bid::forCompany($companyId)->filtered($companyId)->where('company_bid.is_bookmarked', true)->count();
        $relevantCount = Bid::forCompany($companyId)->filtered($companyId)->relevant()->count();

        return view('convocatorias.index', compact('bids', 'statuses', 'methods', 'bookmarkCount', 'relevantCount'));
    }

    /**
     * Return bid detail JSON for the slide-over drawer.
     */
    public function detail(Bid $bid)
    {
        $companyId = currentCompany()->id;
        $pivot = CompanyBid::where('bid_id', $bid->id)->where('company_id', $companyId)->first();

        // Build cronograma from raw_data date fields
        $cronograma = $this->buildCronograma($bid);

        // Check cache freshness (1 hour TTL)
        $needsRefresh = ! $bid->cache_refreshed_at || $bid->cache_refreshed_at->lt(now()->subHour());

        return response()->json([
            'bid' => [
                'id' => $bid->id,
                'process_code' => $bid->process_code,
                'title' => $bid->title,
                'buyer_name' => $bid->buyer_name,
                'status' => $bid->status,
                'amount_estimated' => $bid->amount_estimated,
                'currency' => $bid->currency,
                'published_at' => $bid->published_at?->format('d/m/Y h:i A'),
                'tender_deadline' => $bid->tender_deadline?->format('d/m/Y h:i A'),
                'tender_deadline_past' => $bid->tender_deadline?->isPast() ?? false,
                'secp_url' => $bid->secp_url,
                'procurement_method' => $bid->procurement_method,
                'is_bookmarked' => (bool) $pivot?->is_bookmarked,
                'is_watched' => BidWatch::where('bid_id', $bid->id)->where('user_id', Auth::id())->exists(),
                'mipymes' => $bid->mipymes,
                'mipymes_mujeres' => $bid->mipymes_mujeres,
                'matched_rubros' => $pivot?->matched_rubros ?? [],
                'has_offer' => $bid->offers()->exists(),
                'offer_id' => $bid->offers()->first()?->id,
                'on_tablero' => $bid->offers()->exists(),
            ],
            'cronograma' => $cronograma,
            'institution' => $this->buildInstitutionInfo($bid),
            'cache_stale' => $needsRefresh,
        ]);
    }

    /**
     * Fetch documents/articles/contracts from API (lazy-loaded by drawer tabs).
     */
    public function tabData(Bid $bid, Request $request, DgcpApiClient $api)
    {
        $tab = $request->input('tab', 'documentos');
        $forceRefresh = $request->boolean('refresh');

        $cacheField = match ($tab) {
            'documentos' => 'cached_documents',
            'articulos' => 'cached_articles',
            'adjudicacion' => 'cached_contracts',
            default => null,
        };

        if (! $cacheField) {
            return response()->json(['error' => 'Invalid tab'], 400);
        }

        // Use cache if fresh
        $cacheStale = ! $bid->cache_refreshed_at || $bid->cache_refreshed_at->lt(now()->subHour());
        if (! $forceRefresh && ! $cacheStale && $bid->{$cacheField}) {
            return response()->json(['data' => $bid->{$cacheField}, 'cached' => true]);
        }

        // Fetch from API
        try {
            $data = match ($tab) {
                'documentos' => $api->fetchDocuments($bid->process_code),
                'articulos' => $api->fetchProcessArticles($bid->process_code),
                'adjudicacion' => $this->fetchAdjudicacionData($api, $bid->process_code),
            };

            $isPortalScrape = ($bid->raw_data['source'] ?? null) === 'portal_scrape';
            $isPending = $isPortalScrape
                && in_array($tab, ['documentos', 'articulos'], true)
                && empty($data);

            if (! $isPending) {
                $bid->update([
                    $cacheField => $data,
                    'cache_refreshed_at' => now(),
                ]);
            }

            return response()->json(['data' => $data, 'cached' => false, 'pending' => $isPending]);
        } catch (\Throwable $e) {
            // Fall back to stale cache if available
            if ($bid->{$cacheField}) {
                return response()->json(['data' => $bid->{$cacheField}, 'cached' => true, 'error' => 'API error, showing cached data']);
            }

            return response()->json(['data' => [], 'error' => 'No se pudo obtener datos de la API'], 500);
        }
    }

    /**
     * Toggle bookmark on a bid.
     */
    public function bookmark(Bid $bid)
    {
        $companyId = currentCompany()->id;
        $pivot = CompanyBid::firstOrCreate(
            ['bid_id' => $bid->id, 'company_id' => $companyId],
            ['first_matched_at' => now()]
        );
        $pivot->update(['is_bookmarked' => ! $pivot->is_bookmarked]);

        return response()->json(['bookmarked' => (bool) $pivot->is_bookmarked]);
    }

    /**
     * Toggle watch on a bid for the current user.
     */
    public function watch(Bid $bid)
    {
        $companyId = currentCompany()->id;
        $existing = BidWatch::where('bid_id', $bid->id)->where('user_id', Auth::id())->first();

        if ($existing) {
            $existing->delete();
            $watched = false;
        } else {
            BidWatch::create(['bid_id' => $bid->id, 'user_id' => Auth::id(), 'company_id' => $companyId]);

            // Watching auto-activates bookmark on company_bid pivot
            $pivot = CompanyBid::firstOrCreate(
                ['bid_id' => $bid->id, 'company_id' => $companyId],
                ['first_matched_at' => now()]
            );
            if (! $pivot->is_bookmarked) {
                $pivot->update(['is_bookmarked' => true]);
            }

            // Snapshot current state for change detection
            if (! $bid->last_known_status) {
                $bid->update([
                    'last_known_status' => $bid->status,
                    'last_known_doc_count' => count($bid->cached_documents ?? []),
                ]);
            }

            $watched = true;
        }

        $pivotBookmarked = CompanyBid::where('bid_id', $bid->id)->where('company_id', $companyId)->value('is_bookmarked');

        return response()->json(['watched' => $watched, 'bookmarked' => (bool) $pivotBookmarked]);
    }

    /**
     * Proxy document download to keep user in-app.
     */
    public function downloadDocument(Bid $bid, Request $request)
    {
        $url = $request->input('url');
        if (! $url || ! str_starts_with($url, 'http')) {
            abort(400, 'URL inválida');
        }
        if (! $this->isAllowedDocumentUrl($url)) {
            abort(403, 'URL de documento no permitida');
        }

        try {
            $response = Http::timeout(30)->get($url);
            if ($response->failed()) {
                // Some remote document hosts intermittently reject server-side fetches.
                // Fall back to direct browser download for this already-validated URL.
                return redirect()->away($url);
            }

            $filename = basename((string) $request->input('filename', 'documento.pdf')) ?: 'documento.pdf';
            $contentType = $response->header('Content-Type') ?? 'application/pdf';

            return response($response->body(), 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->away($url);
        }
    }

    public function downloadPortalDocument(Bid $bid, Request $request, PortalScraperService $scraper)
    {
        $fileId = (string) $request->input('fileId', '');
        $filename = basename((string) $request->input('filename', 'documento.pdf')) ?: 'documento.pdf';

        if (! preg_match('/^\d+$/', $fileId)) {
            abort(400, 'ID de documento inválido');
        }

        $noticeUid = $bid->raw_data['notice_uid'] ?? null;
        if (! $noticeUid) {
            abort(404, 'Documento no disponible');
        }

        $file = $scraper->downloadPortalDocument($noticeUid, $fileId);

        if (! $file) {
            return redirect()->away($bid->secp_url ?? 'https://comunidad.comprasdominicana.gob.do');
        }

        return response($file['body'], 200, [
            'Content-Type' => $file['content_type'],
            'Content-Disposition' => 'attachment; filename="'.($file['filename'] ?: $filename).'"',
        ]);
    }

    private function buildCronograma(Bid $bid): array
    {
        $raw = $bid->raw_data ?? [];
        $events = [];

        $dateFields = [
            'fecha_publicacion' => 'Publicación del aviso de convocatoria',
            'fecha_enmienda' => 'Plazo para enmiendas y adendas',
            'fecha_fin_recepcion_ofertas' => 'Fecha límite de recepción de ofertas',
            'fecha_apertura_ofertas' => 'Apertura de sobres',
            'fecha_habilitacion_oferente' => 'Habilitación de oferentes',
            'fecha_estimada_adjudicacion' => 'Adjudicación estimada',
            'fecha_suscripcion' => 'Suscripción del contrato',
        ];

        foreach ($dateFields as $field => $label) {
            $value = $raw[$field] ?? null;
            if (! $value) {
                continue;
            }

            try {
                $dt = Carbon::parse($value);
                $isPast = $dt->isPast();
                $isNext = ! $isPast && $dt->isFuture();

                $countdown = null;
                if ($isNext && $dt->diffInDays(now()) <= 14) {
                    $countdown = $dt->diffForHumans(['parts' => 2, 'short' => true]);
                }

                $events[] = [
                    'date' => $dt->format('d/m/Y h:i A'),
                    'label' => $label,
                    'is_past' => $isPast,
                    'countdown' => $countdown,
                    'sort' => $dt->timestamp,
                ];
            } catch (\Throwable) {
                continue;
            }
        }

        usort($events, fn ($a, $b) => $a['sort'] <=> $b['sort']);

        return $events;
    }

    private function buildInstitutionInfo(Bid $bid): array
    {
        $raw = $bid->raw_data ?? [];

        return [
            'institucion' => $bid->buyer_name,
            'encargado' => $raw['nombre_encargado'] ?? $raw['encargado'] ?? null,
            'email' => $raw['correo_electronico'] ?? $raw['email_contacto'] ?? null,
            'telefono' => $raw['telefono'] ?? $raw['telefono_contacto'] ?? null,
            'modalidad' => $bid->procurement_method,
            'objeto' => $raw['tipo_objeto'] ?? $raw['objeto_compra'] ?? null,
            'duracion_contrato' => isset($raw['duracion_contrato'])
                ? str_replace(['dias', 'anos'], ['días', 'años'], preg_replace('/(\d+)\s*(?=[a-záéíóú])/iu', '$1 ', $raw['duracion_contrato']))
                : null,
            'proveedores_notificados' => $raw['proveedores_notificados'] ?? null,
        ];
    }

    private function fetchAdjudicacionData(DgcpApiClient $api, string $processCode): array
    {
        $contracts = $api->fetchContracts($processCode);
        $articles = $api->fetchContractArticles($processCode);

        return [
            'contracts' => $contracts,
            'articles' => $articles,
        ];
    }

    private function isAllowedDocumentUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! $host) {
            return false;
        }

        // Reject direct IP hosts (including private/link-local/local)
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        $allowedHosts = config('services.dgcp.allowed_document_hosts', [
            'datosabiertos.dgcp.gob.do',
            'dgcp.gob.do',
        ]);

        foreach ($allowedHosts as $allowed) {
            if ($host === $allowed || Str::endsWith($host, '.'.$allowed)) {
                return true;
            }
        }

        return false;
    }
}
