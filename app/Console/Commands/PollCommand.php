<?php

namespace App\Console\Commands;

use App\Exceptions\DgcpApiException;
use App\Jobs\SendBidNotification;
use App\Jobs\SendWatchedBidChangeNotification;
use App\Models\Bid;
use App\Models\BidWatch;
use App\Models\InAppNotification;
use App\Models\Rubro;
use App\Models\Setting;
use App\Services\DgcpApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PollCommand extends Command
{
    protected $signature = 'secp:poll {--dry-run : Fetch and filter but do not save or notify}';

    protected $description = 'Poll the DGCP API for new procurement processes and notify on matches';

    private const STALE_LOCK_MINUTES = 120;

    private const BACKFILL_BATCH_SIZE = 50;

    private array $logBuffer = [];

    public function handle(DgcpApiClient $api): int
    {
        $this->resetStaleLock();

        Setting::set('poll_status', 'running');
        Setting::set('poll_log', '[]');
        Setting::set('poll_started_at', now()->toDateTimeString());

        $this->progress('Iniciando sondeo...', 'info');

        try {
            return $this->runPoll($api);
        } catch (\Throwable $e) {
            $this->progress("Error fatal: {$e->getMessage()}", 'error');
            Log::error('[SECP] Poll crashed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return self::FAILURE;
        } finally {
            $this->flushLog();
            if (! $this->option('dry-run')) {
                Setting::set('poll_status', 'idle');
            }
        }
    }

    private function runPoll(DgcpApiClient $api): int
    {
        $activeRubros = Rubro::where('active', true)->get();

        if ($activeRubros->isEmpty()) {
            $this->progress('No hay rubros activos configurados. Abortando.', 'warn');

            return self::SUCCESS;
        }

        $lastPolledAt = Setting::get('last_polled_at');
        $globalFrom = $lastPolledAt
            ? (new \DateTime($lastPolledAt))->modify('-24 hours')
            : new \DateTime('-90 days');
        $to = new \DateTime;

        $this->progress("Ventana global: {$globalFrom->format('Y-m-d H:i')} → {$to->format('Y-m-d H:i')}", 'info');
        $this->progress($activeRubros->count().' rubro(s) activo(s) a procesar.', 'info');

        $matchesByProcess = collect();
        $firstArticleByProcess = collect();
        $rubroIndex = 0;

        foreach ($activeRubros as $rubro) {
            $rubroIndex++;

            // New rubros get 90-day backfill; existing ones use global window
            $rubroFrom = $rubro->first_polled_at ? $globalFrom : new \DateTime('-90 days');
            $isNewRubro = ! $rubro->first_polled_at;

            $label = "[{$rubroIndex}/{$activeRubros->count()}] Buscando: {$rubro->code} — {$rubro->name}";
            if ($isNewRubro) {
                $label .= ' [NUEVO — backfill 90d]';
            }
            $this->progress($label, 'info');

            try {
                $articles = $api->fetchArticlesSince($rubro->code, $rubro->level, $rubroFrom);
            } catch (DgcpApiException $e) {
                $this->progress("  Error en {$rubro->code}: {$e->getMessage()}", 'warn');
                Log::warning("[SECP] fetchArticlesSince failed for {$rubro->code}", ['error' => $e->getMessage()]);

                continue;
            }

            // Mark rubro as polled
            if ($isNewRubro) {
                $rubro->update(['first_polled_at' => now()]);
            }

            $this->progress("  → {$articles->count()} artículo(s) encontrado(s).", 'info');

            foreach ($articles as $article) {
                $code = $article['codigo_proceso'] ?? '';
                if (empty($code)) {
                    continue;
                }

                if (! $matchesByProcess->has($code)) {
                    $matchesByProcess->put($code, collect());
                    $firstArticleByProcess->put($code, $article);
                }

                $existing = $matchesByProcess->get($code);
                if (! $existing->contains('code', $rubro->code)) {
                    $existing->push(['code' => $rubro->code, 'name' => $rubro->name]);
                }
            }
        }

        $this->progress("{$matchesByProcess->count()} proceso(s) con coincidencias.", 'info');

        $knownCodes = Bid::whereIn('process_code', $matchesByProcess->keys()->all())
            ->pluck('process_code');

        $newMatches = $matchesByProcess->filter(fn ($rubros, $code) => ! $knownCodes->contains($code));

        $this->progress("{$newMatches->count()} proceso(s) nuevos (no almacenados previamente).", 'info');

        if ($newMatches->isEmpty()) {
            $this->progress('Sin procesos nuevos. Sondeo completo.', 'success');
            if (! $this->option('dry-run')) {
                Setting::set('last_polled_at', $to->format('Y-m-d H:i:s'));
                $this->checkWatchedBids($api);
                $this->cleanup($api);
                $this->refreshAllBidStatuses($api);
                $this->backfillMissingData($api);
                $this->enrichPortalBids($api);
            }

            return self::SUCCESS;
        }

        // Load notification filters (bids are always saved; filters only gate notifications)
        $minEnabled = Setting::get('min_amount_filter') === '1';
        $minValue = (float) (Setting::get('min_amount_value') ?? 0);
        $maxEnabled = Setting::get('max_amount_filter') === '1';
        $maxValue = (float) (Setting::get('max_amount_value') ?? 0);
        $excluded = json_decode(Setting::get('excluded_modalities', '[]'), true) ?: [];
        $openDeadlineEnabled = Setting::get('open_deadline_filter') === '1';

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

            // Always save the bid
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
                'matched_rubros' => $matchedRubros->values()->all(),
                'secp_url' => isset($process['url']) ? preg_replace('#([^:])//+#', '$1/', $process['url']) : "https://comunidad.comprasdominicana.gob.do/Public/Tendering/ContractNoticeManagement/Index?q={$processCode}",
                'raw_data' => $process ?? $firstArticle,
                'mipymes' => filter_var($process['dirigido_mipymes'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'mipymes_mujeres' => filter_var($process['dirigido_mipymes_mujeres'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_relevant' => Bid::computeRelevance($process['titulo'] ?? $firstArticle['descripcion_articulo'] ?? ''),
            ]);

            $saved++;
            $this->progress("[GUARDADO] {$processCode} — ".$matchedRubros->pluck('name')->join(', ').($bid->is_relevant ? ' [RELEVANTE]' : ''), 'match');

            // Apply filters only for notification
            $amount = $bid->amount_estimated;
            $modality = $bid->procurement_method;
            $shouldNotify = true;

            if ($minEnabled && $amount !== null && $amount < $minValue) {
                $this->progress("  [SIN NOTIFICAR] monto {$amount} por debajo del mínimo {$minValue}", 'warn');
                $shouldNotify = false;
            }

            if ($shouldNotify && $maxEnabled && $maxValue > 0 && $amount !== null && $amount > $maxValue) {
                $this->progress("  [SIN NOTIFICAR] monto {$amount} por encima del máximo {$maxValue}", 'warn');
                $shouldNotify = false;
            }

            if ($shouldNotify && $modality && in_array($modality, $excluded)) {
                $this->progress("  [SIN NOTIFICAR] modalidad '{$modality}' excluida", 'warn');
                $shouldNotify = false;
            }

            if ($shouldNotify && $openDeadlineEnabled) {
                if ($bid->tender_deadline && $bid->tender_deadline < now()) {
                    $this->progress("  [SIN NOTIFICAR] plazo vencido ({$bid->tender_deadline->format('Y-m-d H:i')})", 'warn');
                    $shouldNotify = false;
                }
            }

            if ($shouldNotify) {
                SendBidNotification::dispatch($bid);
                $notified++;
            } else {
                // Mark as "notified" so it doesn't appear as a missed notification
                $bid->update(['notified_at' => now()]);
            }
        }

        if (! $this->option('dry-run')) {
            Setting::set('last_polled_at', $to->format('Y-m-d H:i:s'));
            $this->checkWatchedBids($api);
            $this->cleanup($api);
            $this->refreshAllBidStatuses($api);
            $this->backfillMissingData($api);
            $this->enrichPortalBids($api);
        }

        $summary = "Sondeo completo. Coincidencias: {$matchesByProcess->count()} | Nuevos: {$newMatches->count()} | Guardados: {$saved} | Notificados: {$notified}";
        $this->progress($summary, 'success');
        Log::info("[SECP] {$summary}");

        return self::SUCCESS;
    }

    /**
     * If poll_status has been 'running' for longer than STALE_LOCK_MINUTES, reset it.
     * Prevents a crashed poll from permanently blocking future polls.
     */
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

    /**
     * Refresh status for all active bids (not just watched ones).
     * Processes bids that haven't been checked recently, up to 30 per cycle.
     */
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
            ->where('is_bookmarked', false)
            ->delete();

        if ($deleted > 0) {
            $this->progress("Limpieza: {$deleted} convocatoria(s) eliminada(s) por plazo vencido o proceso cerrado.", 'info');
            Log::info("[SECP] Cleanup removed {$deleted} expired/closed bids.");
        }
    }

    /**
     * Re-fetch process details for bids missing critical fields.
     * Processes up to BACKFILL_BATCH_SIZE per poll cycle.
     */
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

            // Always refresh raw_data with latest
            $updates['raw_data'] = $process;

            if (count($updates) > 1) { // >1 because raw_data is always there
                $bid->update($updates);
                $filled = array_diff(array_keys($updates), ['raw_data']);
                $this->progress("  [BACKFILL] {$bid->process_code}: ".implode(', ', $filled), 'info');
            }
        }
    }

    /**
     * Enrich portal-scraped bids with full API data once they appear in the open data API.
     * Updates raw_data, procurement_method, mipymes flags, and matched_rubros.
     */
    private function enrichPortalBids(DgcpApiClient $api): void
    {
        $bids = Bid::where('raw_data->source', 'portal_scrape')
            ->orderBy('created_at', 'asc')
            ->limit(self::BACKFILL_BATCH_SIZE)
            ->get();

        if ($bids->isEmpty()) {
            return;
        }

        $this->progress("Enriqueciendo {$bids->count()} convocatoria(s) del portal...", 'info');
        $enriched = 0;

        foreach ($bids as $bid) {
            try {
                $process = $api->fetchProcessByCode($bid->process_code);
            } catch (\Throwable) {
                continue;
            }

            if (! $process) {
                continue; // Not yet in API — try again next cycle
            }

            // Full enrichment from API
            $updates = [
                'raw_data' => $process, // Replaces portal_scrape marker with real API data
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

            // Try to match rubros via articles
            try {
                $articles = $api->fetchProcessArticles($bid->process_code);
                $activeRubros = Rubro::where('active', true)->get();
                $matchedRubros = collect();

                foreach ($articles as $article) {
                    foreach ($activeRubros as $rubro) {
                        $articleCode = (string) ($article[$rubro->level] ?? '');
                        if ($articleCode === $rubro->code && ! $matchedRubros->contains('code', $rubro->code)) {
                            $matchedRubros->push(['code' => $rubro->code, 'name' => $rubro->name]);
                        }
                    }
                }

                if ($matchedRubros->isNotEmpty()) {
                    $updates['matched_rubros'] = $matchedRubros->values()->all();
                    $this->progress("  [RUBROS] {$bid->process_code}: ".$matchedRubros->pluck('name')->join(', '), 'match');
                }
            } catch (\Throwable) {
                // Articles not available yet — keep portal marker
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
        $watchedBidIds = BidWatch::select('bid_id')->distinct()->pluck('bid_id');
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

            // Check status change
            $newStatus = $process['estado_proceso'] ?? null;
            if ($newStatus && $bid->last_known_status && $newStatus !== $bid->last_known_status) {
                $changes[] = "Estado: {$bid->last_known_status} → {$newStatus}";
            }

            // Check deadline change
            $newDeadline = $this->parseDate($process['fecha_fin_recepcion_ofertas'] ?? null);
            if ($bid->tender_deadline && $newDeadline && $newDeadline->format('Y-m-d H:i') !== $bid->tender_deadline->format('Y-m-d H:i')) {
                $changes[] = "Plazo: {$bid->tender_deadline->format('d/m/Y H:i')} → {$newDeadline->format('d/m/Y H:i')}";
            }

            // Check amount change
            $newAmount = $this->parseAmount($process['monto_estimado'] ?? null);
            if ($bid->amount_estimated && $newAmount && abs($newAmount - $bid->amount_estimated) > 0.01) {
                $oldFormatted = number_format($bid->amount_estimated, 2);
                $newFormatted = number_format($newAmount, 2);
                $changes[] = "Monto: {$oldFormatted} → {$newFormatted}";
            }

            // Check document count change
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

            // Check amendment date change
            $newEnmienda = $process['fecha_enmienda'] ?? null;
            $oldEnmienda = $bid->raw_data['fecha_enmienda'] ?? null;
            if ($newEnmienda && $newEnmienda !== $oldEnmienda) {
                $changes[] = 'Enmienda publicada';
            }

            // Check procurement method change
            $newMethod = $process['modalidad'] ?? null;
            if ($newMethod && $bid->procurement_method && $newMethod !== $bid->procurement_method) {
                $changes[] = "Modalidad: {$bid->procurement_method} → {$newMethod}";
            }

            // Backfill missing dates/amounts (not counted as "changes")
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

            // Update bid with new state
            $bid->update([
                'status' => $newStatus ?? $bid->status,
                'last_known_status' => $newStatus ?? $bid->status,
                'last_known_doc_count' => $newDocCount,
                'tender_deadline' => $newDeadline ?? $bid->tender_deadline,
                'amount_estimated' => $newAmount ?? $bid->amount_estimated,
                'procurement_method' => $newMethod ?? $bid->procurement_method,
                'raw_data' => $process,
            ]);

            // Notify all watchers: in-app + email + telegram
            $watcherUserIds = BidWatch::where('bid_id', $bid->id)->pluck('user_id');
            $changeText = implode(', ', $changes);

            foreach ($watcherUserIds as $userId) {
                InAppNotification::create([
                    'user_id' => $userId,
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

            // Dispatch email + telegram for watched bid change
            SendWatchedBidChangeNotification::dispatch($bid, $changes);
        }
    }

    /**
     * Buffer log lines in memory, flush to DB periodically and on completion.
     */
    private function progress(string $message, string $type = 'info'): void
    {
        $this->line($message);

        $this->logBuffer[] = ['time' => now()->format('H:i:s'), 'msg' => $message, 'type' => $type];

        // Flush every 20 lines to keep the frontend updated without per-line DB writes
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

        if (count($merged) > 300) {
            $merged = array_slice($merged, -300);
        }

        Setting::set('poll_log', json_encode($merged));
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
