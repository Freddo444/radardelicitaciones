<?php

namespace App\Console\Commands;

use App\Exceptions\DgcpApiException;
use App\Jobs\SendBidNotification;
use App\Jobs\SendWatchedBidChangeNotification;
use App\Models\Bid;
use App\Models\BidWatch;
use App\Models\Company;
use App\Models\CompanyBid;
use App\Models\InAppNotification;
use App\Models\Setting;
use App\Services\BidMatchingService;
use App\Services\DgcpApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PollCommand extends Command
{
    protected $signature = 'secp:poll {--dry-run : Fetch and filter but do not save or notify}';

    protected $description = 'Poll the DGCP API for new procurement processes and notify on matches';

    private const STALE_LOCK_MINUTES = 120;

    private const BACKFILL_BATCH_SIZE = 50;

    private const MAX_POLL_LOG_BYTES = 60000;

    private const MAX_LOG_ENTRIES = 300;

    private const MAX_LOG_MESSAGE_CHARS = 300;

    private array $logBuffer = [];

    public function handle(DgcpApiClient $api, BidMatchingService $matcher): int
    {
        $this->resetStaleLock();

        Setting::set('poll_status', 'running');
        Setting::set('poll_log', '[]');
        Setting::set('poll_started_at', now()->toDateTimeString());

        $this->progress('Iniciando sondeo...', 'info');

        try {
            return $this->runPoll($api, $matcher);
        } catch (\Throwable $e) {
            $this->progress("Error fatal: {$e->getMessage()}", 'error');
            Log::error('[SECP] Poll crashed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return self::FAILURE;
        } finally {
            try {
                $this->flushLog();
            } catch (\Throwable $e) {
                Log::error('[SECP] Poll log flush failed', ['error' => $e->getMessage()]);
            }

            if (! $this->option('dry-run')) {
                try {
                    Setting::set('poll_status', 'idle');
                } catch (\Throwable $e) {
                    Log::error('[SECP] Failed to reset poll_status to idle', ['error' => $e->getMessage()]);
                }
            }
        }
    }

    private function runPoll(DgcpApiClient $api, BidMatchingService $matcher): int
    {
        // Aggregate rubros across all companies, deduplicating by code
        $rubroMap = $matcher->aggregateRubros();

        if (empty($rubroMap)) {
            $this->progress('No hay rubros activos en ninguna empresa. Abortando.', 'warn');

            return self::SUCCESS;
        }

        $companyCount = count(array_unique(array_merge(...array_column($rubroMap, 'company_ids'))));
        $this->progress(count($rubroMap)." rubro(s) únicos activo(s) de {$companyCount} empresa(s).", 'info');

        $lastPolledAt = Setting::get('last_polled_at');
        $globalFrom = $lastPolledAt
            ? (new \DateTime($lastPolledAt))->modify('-24 hours')
            : new \DateTime('-90 days');
        $to = new \DateTime;

        $this->progress("Ventana global: {$globalFrom->format('Y-m-d H:i')} → {$to->format('Y-m-d H:i')}", 'info');

        $matchesByProcess = collect();
        $firstArticleByProcess = collect();
        $rubroIndex = 0;
        $totalRubros = count($rubroMap);

        foreach ($rubroMap as $code => $entry) {
            $rubroIndex++;

            // New rubros (never polled) get 90-day backfill; existing ones use global window
            $isNew = $entry['first_polled_at'] === null;
            $rubroFrom = $isNew ? new \DateTime('-90 days') : $globalFrom;

            $label = "[{$rubroIndex}/{$totalRubros}] Buscando: {$code} — {$entry['name']}";
            $label .= ' ['.count($entry['company_ids']).' empresa(s)]';
            if ($isNew) {
                $label .= ' [NUEVO — backfill 90d]';
            }
            $this->progress($label, 'info');

            try {
                $articles = $api->fetchArticlesSince($code, $entry['level'], $rubroFrom);
            } catch (DgcpApiException $e) {
                $this->progress("  Error en {$code}: {$e->getMessage()}", 'warn');
                Log::warning("[SECP] fetchArticlesSince failed for {$code}", ['error' => $e->getMessage()]);

                continue;
            }

            // Mark all instances of this rubro code as polled
            if ($isNew) {
                $matcher->markRubrosPolled($code);
            }

            $this->progress("  → {$articles->count()} artículo(s) encontrado(s).", 'info');

            foreach ($articles as $article) {
                $processCode = $article['codigo_proceso'] ?? '';
                if (empty($processCode)) {
                    continue;
                }

                if (! $matchesByProcess->has($processCode)) {
                    $matchesByProcess->put($processCode, collect());
                    $firstArticleByProcess->put($processCode, $article);
                }

                $existing = $matchesByProcess->get($processCode);
                if (! $existing->contains('code', $code)) {
                    $existing->push(['code' => $code, 'name' => $entry['name']]);
                }
            }
        }

        $this->progress("{$matchesByProcess->count()} proceso(s) con coincidencias.", 'info');

        $knownCodes = Bid::whereIn('process_code', $matchesByProcess->keys()->all())
            ->pluck('process_code');

        $newMatches = $matchesByProcess->filter(fn ($rubros, $code) => ! $knownCodes->contains($code));

        $this->progress("{$newMatches->count()} proceso(s) nuevos (no almacenados previamente).", 'info');

        // Fan out existing (already-stored) bids to companies that didn't have them yet
        $existingMatches = $matchesByProcess->filter(fn ($rubros, $code) => $knownCodes->contains($code));
        if ($existingMatches->isNotEmpty()) {
            $this->fanOutExistingBids($existingMatches, $rubroMap, $matcher);
        }

        if ($newMatches->isEmpty()) {
            $this->progress('Sin procesos nuevos. Sondeo completo.', 'success');
            if (! $this->option('dry-run')) {
                Setting::set('last_polled_at', $to->format('Y-m-d H:i:s'));
                $this->checkWatchedBids($api);
                $this->cleanup($api);
                $this->refreshAllBidStatuses($api);
                $this->backfillMissingData($api);
                $this->enrichPortalBids($api, $matcher);
            }

            return self::SUCCESS;
        }

        $this->progress('Obteniendo detalles de '.$newMatches->count().' proceso(s)...', 'info');

        $saved = 0;
        $notified = 0;

        foreach ($newMatches as $processCode => $matchedRubros) {
            try {
                $process = $api->fetchProcessByCode($processCode);
            } catch (DgcpApiException $e) {
                $this->progress("Advertencia: no se pudo obtener detalles de {$processCode}: {$e->getMessage()}", 'warn');
                $process = null;
            }

            $firstArticle = $firstArticleByProcess->get($processCode, []);

            if ($this->option('dry-run')) {
                $this->progress("[DRY] {$processCode} — ".$matchedRubros->pluck('name')->join(', '), 'match');

                continue;
            }

            // Create global bid record
            $bid = Bid::create([
                'process_code' => $processCode,
                'ocid' => $process['ocid'] ?? ('ocds-6550wx-'.$processCode),
                'title' => $process['titulo'] ?? $firstArticle['descripcion_articulo'] ?? $processCode,
                'buyer_name' => $process['unidad_compra'] ?? null,
                'buyer_code' => $process['codigo_unidad_compra'] ?? null,
                'procurement_method' => $process['modalidad'] ?? null,
                'status' => $process['estado_proceso'] ?? null,
                'amount_estimated' => $this->parseAmount($process['monto_estimado'] ?? null),
                'currency' => $process['divisa'] ?? 'DOP',
                'published_at' => $this->parseDate($process['fecha_publicacion'] ?? $firstArticle['fecha_publicacion'] ?? null),
                'tender_deadline' => $this->parseDate($process['fecha_fin_recepcion_ofertas'] ?? null),
                'secp_url' => isset($process['url']) ? preg_replace('#([^:])//+#', '$1/', $process['url']) : "https://comunidad.comprasdominicana.gob.do/Public/Tendering/ContractNoticeManagement/Index?q={$processCode}",
                'raw_data' => $process ?? $firstArticle,
                'mipymes' => filter_var($process['dirigido_mipymes'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'mipymes_mujeres' => filter_var($process['dirigido_mipymes_mujeres'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ]);

            $saved++;

            // Fan out to matching companies
            $companyIds = $matcher->fanOutToCompanies($bid, $matchedRubros->values()->all(), $rubroMap);

            $this->progress("[GUARDADO] {$processCode} — ".$matchedRubros->pluck('name')->join(', ').' ['.count($companyIds).' empresa(s)]', 'match');

            // Per-company notification dispatch
            foreach ($companyIds as $companyId) {
                if ($matcher->shouldNotify($bid, $companyId)) {
                    $company = Company::find($companyId);
                    if ($company) {
                        SendBidNotification::dispatch($bid, $company);
                        $notified++;
                    }
                } else {
                    // Mark as notified so it doesn't appear as missed
                    CompanyBid::where('bid_id', $bid->id)
                        ->where('company_id', $companyId)
                        ->update(['notified_at' => now()]);
                }
            }
        }

        if (! $this->option('dry-run')) {
            Setting::set('last_polled_at', $to->format('Y-m-d H:i:s'));
            $this->checkWatchedBids($api);
            $this->cleanup($api);
            $this->refreshAllBidStatuses($api);
            $this->backfillMissingData($api);
            $this->enrichPortalBids($api, $matcher);
        }

        $summary = "Sondeo completo. Coincidencias: {$matchesByProcess->count()} | Nuevos: {$newMatches->count()} | Guardados: {$saved} | Notificados: {$notified}";
        $this->progress($summary, 'success');
        Log::info("[SECP] {$summary}");

        return self::SUCCESS;
    }

    /**
     * For bids already in DB, create company_bid pivots for any companies that don't have them yet.
     */
    private function fanOutExistingBids($existingMatches, array $rubroMap, BidMatchingService $matcher): void
    {
        $bids = Bid::whereIn('process_code', $existingMatches->keys()->all())
            ->get()
            ->keyBy('process_code');

        $newPivots = 0;

        foreach ($existingMatches as $processCode => $matchedRubros) {
            $bid = $bids->get($processCode);
            if (! $bid) {
                continue;
            }

            // Determine which companies should have this bid
            $companyIds = [];
            foreach ($matchedRubros as $match) {
                $code = $match['code'];
                if (isset($rubroMap[$code])) {
                    foreach ($rubroMap[$code]['company_ids'] as $cid) {
                        $companyIds[$cid] = true;
                    }
                }
            }

            // Check which companies already have this bid
            $existingCompanyIds = CompanyBid::where('bid_id', $bid->id)
                ->whereIn('company_id', array_keys($companyIds))
                ->pluck('company_id')
                ->all();

            $missingCompanyIds = array_diff(array_keys($companyIds), $existingCompanyIds);

            foreach ($missingCompanyIds as $companyId) {
                // Build per-company matched rubros
                $companyRubros = [];
                foreach ($matchedRubros as $match) {
                    if (isset($rubroMap[$match['code']]) && in_array($companyId, $rubroMap[$match['code']]['company_ids'])) {
                        $companyRubros[] = $match;
                    }
                }

                CompanyBid::create([
                    'company_id' => $companyId,
                    'bid_id' => $bid->id,
                    'matched_rubros' => $companyRubros,
                    'is_relevant' => Bid::computeRelevance($bid->title ?? '', $companyId),
                    'first_matched_at' => now(),
                ]);
                $newPivots++;
            }
        }

        if ($newPivots > 0) {
            $this->progress("  {$newPivots} nueva(s) vinculación(es) empresa-convocatoria creada(s) para procesos existentes.", 'info');
        }
    }

    private function resetStaleLock(): void
    {
        if (Setting::get('poll_status') !== 'running') {
            return;
        }

        $startedAt = Setting::get('poll_started_at');
        if (! $startedAt) {
            Setting::set('poll_status', 'idle');
            Log::warning('[SECP] Reset stale poll lock (no started_at timestamp)');

            return;
        }

        $minutesRunning = now()->diffInMinutes(new \DateTime($startedAt));
        if ($minutesRunning >= self::STALE_LOCK_MINUTES) {
            Setting::set('poll_status', 'idle');
            Log::warning("[SECP] Reset stale poll lock after {$minutesRunning} minutes");
            $this->progress("Lock anterior reseteado ({$minutesRunning} min sin respuesta).", 'warn');
        }
    }

    private function refreshAllBidStatuses(DgcpApiClient $api): void
    {
        $bids = Bid::whereNotNull('status')
            ->whereNotIn('status', ['Cancelado', 'Proceso adjudicado y celebrado', 'Proceso desierto'])
            ->whereNotNull('tender_deadline')
            ->where('tender_deadline', '>=', now()->subDays(7))
            ->orderByRaw('COALESCE(updated_at, created_at) ASC')
            ->limit(30)
            ->get();

        if ($bids->isEmpty()) {
            return;
        }

        $this->progress("Actualizando estado de {$bids->count()} convocatoria(s) activa(s)...", 'info');
        $updated = 0;

        foreach ($bids as $bid) {
            try {
                $process = $api->fetchProcessByCode($bid->process_code);
            } catch (\Throwable) {
                continue;
            }

            if (! $process) {
                continue;
            }

            $newStatus = $process['estado_proceso'] ?? null;
            if ($newStatus && $newStatus !== $bid->status) {
                $bid->update([
                    'status' => $newStatus,
                    'raw_data' => $process,
                ]);
                $updated++;
                $this->progress("  [ESTADO] {$bid->process_code}: {$bid->getOriginal('status')} → {$newStatus}", 'info');
            }
        }

        if ($updated > 0) {
            $this->progress("  {$updated} estado(s) actualizado(s).", 'info');
        }
    }

    private function cleanup(DgcpApiClient $api): void
    {
        $closedStatuses = [
            'Cancelado',
            'Proceso adjudicado y celebrado',
            'Proceso desierto',
        ];

        $deleted = Bid::where(function ($q) use ($closedStatuses) {
            $q->where(function ($q2) {
                $q2->whereNotNull('tender_deadline')
                    ->where('tender_deadline', '<', now());
            })->orWhereIn('status', $closedStatuses);
        })
            ->whereDoesntHave('offers')
            ->whereDoesntHave('watches')
            ->whereDoesntHave('companies', fn ($q) => $q->where('company_bid.is_bookmarked', true))
            ->delete();

        if ($deleted > 0) {
            $this->progress("Limpieza: {$deleted} convocatoria(s) eliminada(s) por plazo vencido o proceso cerrado.", 'info');
            Log::info("[SECP] Cleanup removed {$deleted} expired/closed bids.");
        }
    }

    private function backfillMissingData(DgcpApiClient $api): void
    {
        $bids = Bid::where(function ($q) {
            $q->whereNull('tender_deadline')
                ->orWhereNull('published_at')
                ->orWhereNull('amount_estimated');
        })->limit(self::BACKFILL_BATCH_SIZE)->get();

        if ($bids->isEmpty()) {
            return;
        }

        $this->progress("Backfill: revisando {$bids->count()} convocatoria(s) con datos faltantes...", 'info');

        foreach ($bids as $bid) {
            try {
                $process = $api->fetchProcessByCode($bid->process_code);
            } catch (\Throwable) {
                continue;
            }

            if (! $process) {
                continue;
            }

            $updates = [];

            if (! $bid->tender_deadline && ($process['fecha_fin_recepcion_ofertas'] ?? null)) {
                $updates['tender_deadline'] = $this->parseDate($process['fecha_fin_recepcion_ofertas']);
            }
            if (! $bid->published_at && ($process['fecha_publicacion'] ?? null)) {
                $updates['published_at'] = $this->parseDate($process['fecha_publicacion']);
            }
            if (! $bid->amount_estimated && ($process['monto_estimado'] ?? null)) {
                $updates['amount_estimated'] = $this->parseAmount($process['monto_estimado']);
            }
            if (! $bid->buyer_name && ($process['unidad_compra'] ?? null)) {
                $updates['buyer_name'] = $process['unidad_compra'];
            }
            if (! $bid->status && ($process['estado_proceso'] ?? null)) {
                $updates['status'] = $process['estado_proceso'];
            }

            $updates['raw_data'] = $process;

            if (count($updates) > 1) {
                $bid->update($updates);
                $filled = array_diff(array_keys($updates), ['raw_data']);
                $this->progress("  [BACKFILL] {$bid->process_code}: ".implode(', ', $filled), 'info');
            }
        }
    }

    private function enrichPortalBids(DgcpApiClient $api, BidMatchingService $matcher): void
    {
        $bids = Bid::where('raw_data->source', 'portal_scrape')
            ->orderBy('created_at', 'asc')
            ->limit(self::BACKFILL_BATCH_SIZE)
            ->get();

        if ($bids->isEmpty()) {
            return;
        }

        $rubroMap = $matcher->aggregateRubros();

        $this->progress("Enriqueciendo {$bids->count()} convocatoria(s) del portal...", 'info');
        $enriched = 0;

        foreach ($bids as $bid) {
            try {
                $process = $api->fetchProcessByCode($bid->process_code);
            } catch (\Throwable) {
                continue;
            }

            if (! $process) {
                continue;
            }

            $updates = [
                'raw_data' => $process,
                'ocid' => $process['ocid'] ?? $bid->ocid,
                'title' => $process['titulo'] ?? $bid->title,
                'buyer_name' => $process['unidad_compra'] ?? $bid->buyer_name,
                'buyer_code' => $process['codigo_unidad_compra'] ?? $bid->buyer_code,
                'procurement_method' => $process['modalidad'] ?? $bid->procurement_method,
                'status' => $process['estado_proceso'] ?? $bid->status,
                'mipymes' => filter_var($process['dirigido_mipymes'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'mipymes_mujeres' => filter_var($process['dirigido_mipymes_mujeres'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'secp_url' => isset($process['url'])
                    ? preg_replace('#([^:])//+#', '$1/', $process['url'])
                    : $bid->secp_url,
            ];

            if (! $bid->amount_estimated && ($process['monto_estimado'] ?? null)) {
                $updates['amount_estimated'] = $this->parseAmount($process['monto_estimado']);
            }
            if (! $bid->tender_deadline && ($process['fecha_fin_recepcion_ofertas'] ?? null)) {
                $updates['tender_deadline'] = $this->parseDate($process['fecha_fin_recepcion_ofertas']);
            }
            if (! $bid->published_at && ($process['fecha_publicacion'] ?? null)) {
                $updates['published_at'] = $this->parseDate($process['fecha_publicacion']);
            }

            // Try to match rubros via articles and fan out to companies
            try {
                $articles = $api->fetchProcessArticles($bid->process_code);
                $matchedRubros = collect();

                foreach ($articles as $article) {
                    foreach ($rubroMap as $code => $entry) {
                        $articleCode = (string) ($article[$entry['level']] ?? '');
                        if ($articleCode === $code && ! $matchedRubros->contains('code', $code)) {
                            $matchedRubros->push(['code' => $code, 'name' => $entry['name']]);
                        }
                    }
                }

                if ($matchedRubros->isNotEmpty()) {
                    $matcher->fanOutToCompanies($bid, $matchedRubros->values()->all(), $rubroMap);
                    $this->progress("  [RUBROS] {$bid->process_code}: ".$matchedRubros->pluck('name')->join(', '), 'match');
                }
            } catch (\Throwable) {
                // Articles not available yet
            }

            $bid->update($updates);
            $enriched++;
            $this->progress("  [ENRIQUECIDO] {$bid->process_code}", 'info');
        }

        if ($enriched > 0) {
            $this->progress("  {$enriched} convocatoria(s) enriquecida(s) con datos de API.", 'info');
        }
    }

    private function checkWatchedBids(DgcpApiClient $api): void
    {
        $watchedBidIds = BidWatch::withoutGlobalScopes()
            ->select('bid_id')
            ->distinct()
            ->pluck('bid_id');

        if ($watchedBidIds->isEmpty()) {
            return;
        }

        $bids = Bid::whereIn('id', $watchedBidIds)->get();
        $this->progress("Revisando {$bids->count()} convocatoria(s) vigilada(s)...", 'info');

        foreach ($bids as $bid) {
            try {
                $process = $api->fetchProcessByCode($bid->process_code);
            } catch (\Throwable $e) {
                $this->progress("  Error revisando {$bid->process_code}: {$e->getMessage()}", 'warn');

                continue;
            }

            if (! $process) {
                continue;
            }

            $changes = [];

            $newStatus = $process['estado_proceso'] ?? null;
            if ($newStatus && $bid->last_known_status && $newStatus !== $bid->last_known_status) {
                $changes[] = "Estado: {$bid->last_known_status} → {$newStatus}";
            }

            $newDeadline = $this->parseDate($process['fecha_fin_recepcion_ofertas'] ?? null);
            if ($bid->tender_deadline && $newDeadline && $newDeadline->format('Y-m-d H:i') !== $bid->tender_deadline->format('Y-m-d H:i')) {
                $changes[] = "Plazo: {$bid->tender_deadline->format('d/m/Y H:i')} → {$newDeadline->format('d/m/Y H:i')}";
            }

            $newAmount = $this->parseAmount($process['monto_estimado'] ?? null);
            if ($bid->amount_estimated && $newAmount && abs($newAmount - $bid->amount_estimated) > 0.01) {
                $oldFormatted = number_format($bid->amount_estimated, 2);
                $newFormatted = number_format($newAmount, 2);
                $changes[] = "Monto: {$oldFormatted} → {$newFormatted}";
            }

            try {
                $docs = $api->fetchDocuments($bid->process_code);
                $newDocCount = count($docs);
            } catch (\Throwable) {
                $newDocCount = $bid->last_known_doc_count;
            }

            if ($bid->last_known_doc_count !== null && $newDocCount > $bid->last_known_doc_count) {
                $diff = $newDocCount - $bid->last_known_doc_count;
                $changes[] = "{$diff} documento(s) nuevo(s)";
            }

            $newEnmienda = $process['fecha_enmienda'] ?? null;
            $oldEnmienda = $bid->raw_data['fecha_enmienda'] ?? null;
            if ($newEnmienda && $newEnmienda !== $oldEnmienda) {
                $changes[] = 'Enmienda publicada';
            }

            $newMethod = $process['modalidad'] ?? null;
            if ($newMethod && $bid->procurement_method && $newMethod !== $bid->procurement_method) {
                $changes[] = "Modalidad: {$bid->procurement_method} → {$newMethod}";
            }

            // Backfill missing dates/amounts
            $backfill = [];
            if (! $bid->tender_deadline && $newDeadline) {
                $backfill['tender_deadline'] = $newDeadline;
            }
            if (! $bid->published_at && ($process['fecha_publicacion'] ?? null)) {
                $backfill['published_at'] = $this->parseDate($process['fecha_publicacion']);
            }
            if (! $bid->amount_estimated && $newAmount) {
                $backfill['amount_estimated'] = $newAmount;
            }

            if (! empty($backfill)) {
                $bid->update($backfill);
                $this->progress("  [BACKFILL] {$bid->process_code}: ".implode(', ', array_keys($backfill)), 'info');
            }

            if (empty($changes)) {
                continue;
            }

            $this->progress("  [CAMBIO] {$bid->process_code}: ".implode(', ', $changes), 'match');

            $bid->update([
                'status' => $newStatus ?? $bid->status,
                'last_known_status' => $newStatus ?? $bid->status,
                'last_known_doc_count' => $newDocCount,
                'tender_deadline' => $newDeadline ?? $bid->tender_deadline,
                'amount_estimated' => $newAmount ?? $bid->amount_estimated,
                'procurement_method' => $newMethod ?? $bid->procurement_method,
                'raw_data' => $process,
            ]);

            // Notify watchers per-company
            $watches = BidWatch::withoutGlobalScopes()
                ->where('bid_id', $bid->id)
                ->get();

            // Group watchers by company
            $watchersByCompany = $watches->groupBy('company_id');
            $changeText = implode(', ', $changes);

            foreach ($watchersByCompany as $companyId => $companyWatches) {
                // In-app notifications for each watcher in this company
                foreach ($companyWatches as $watch) {
                    InAppNotification::create([
                        'company_id' => $companyId,
                        'user_id' => $watch->user_id,
                        'bid_id' => $bid->id,
                        'type' => 'status_changed',
                        'title' => $bid->title,
                        'body' => $changeText,
                        'data' => [
                            'process_code' => $bid->process_code,
                            'changes' => $changes,
                        ],
                    ]);
                }

                // Email + Telegram per-company
                $company = Company::find($companyId);
                if ($company) {
                    SendWatchedBidChangeNotification::dispatch($bid, $changes, $company);
                }
            }
        }
    }

    private function progress(string $message, string $type = 'info'): void
    {
        $this->line($message);

        $this->logBuffer[] = [
            'time' => now()->format('H:i:s'),
            'msg' => mb_strimwidth($message, 0, self::MAX_LOG_MESSAGE_CHARS, '...'),
            'type' => $type,
        ];

        if (count($this->logBuffer) >= 20) {
            $this->flushLog();
        }
    }

    private function flushLog(): void
    {
        if (empty($this->logBuffer)) {
            return;
        }

        $existing = json_decode(Setting::get('poll_log', '[]'), true) ?: [];
        $merged = array_merge($existing, $this->logBuffer);

        if (count($merged) > self::MAX_LOG_ENTRIES) {
            $merged = array_slice($merged, -self::MAX_LOG_ENTRIES);
        }

        $encoded = json_encode($merged, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            $encoded = '[]';
        }

        while (strlen($encoded) > self::MAX_POLL_LOG_BYTES && count($merged) > 1) {
            array_shift($merged);
            $encoded = json_encode($merged, JSON_UNESCAPED_UNICODE) ?: '[]';
        }

        Setting::set('poll_log', $encoded);
        $this->logBuffer = [];
    }

    private function parseAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $cleaned = preg_replace('/[^\d.]/', '', (string) $value);

        return $cleaned !== '' ? (float) $cleaned : null;
    }

    private function parseDate(mixed $value): ?\DateTime
    {
        if (empty($value)) {
            return null;
        }
        try {
            return new \DateTime($value);
        } catch (\Exception) {
            return null;
        }
    }
}
