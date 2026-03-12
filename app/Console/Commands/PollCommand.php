<?php

namespace App\Console\Commands;

use App\Exceptions\DgcpApiException;
use App\Jobs\SendBidNotification;
use App\Models\Bid;
use App\Models\Rubro;
use App\Models\Setting;
use App\Services\DgcpApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PollCommand extends Command
{
    protected $signature = 'secp:poll {--dry-run : Fetch and filter but do not save or notify}';

    protected $description = 'Poll the DGCP API for new procurement processes and notify on matches';

    public function handle(DgcpApiClient $api): int
    {
        $this->progress('Iniciando sondeo...', 'info');

        Setting::set('poll_status', 'running');
        Setting::set('poll_log', '[]');
        Setting::set('poll_started_at', now()->toDateTimeString());

        $activeRubros = Rubro::where('active', true)->get();

        if ($activeRubros->isEmpty()) {
            $this->progress('No hay rubros activos configurados. Abortando.', 'warn');
            Setting::set('poll_status', 'idle');

            return self::SUCCESS;
        }

        $lastPolledAt = Setting::get('last_polled_at');
        $from = $lastPolledAt
            ? new \DateTime($lastPolledAt)
            : new \DateTime('-24 hours');
        $to = new \DateTime;

        $this->progress("Ventana: {$from->format('Y-m-d H:i')} → {$to->format('Y-m-d H:i')}", 'info');
        $this->progress($activeRubros->count().' rubro(s) activo(s) a procesar.', 'info');

        $matchesByProcess = collect();
        $firstArticleByProcess = collect();
        $rubroIndex = 0;

        foreach ($activeRubros as $rubro) {
            $rubroIndex++;
            $this->progress("[{$rubroIndex}/{$activeRubros->count()}] Buscando: {$rubro->code} — {$rubro->name}", 'info');

            try {
                $articles = $api->fetchArticlesSince($rubro->code, $rubro->level, $from);
            } catch (DgcpApiException $e) {
                $this->progress("  Error en {$rubro->code}: {$e->getMessage()}", 'warn');
                Log::warning("[SECP] fetchArticlesSince failed for {$rubro->code}", ['error' => $e->getMessage()]);

                continue;
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

        $newMatches = $matchesByProcess->filter(fn ($_, $code) => ! $knownCodes->contains($code));

        $this->progress("{$newMatches->count()} proceso(s) nuevos (no almacenados previamente).", 'info');

        if ($newMatches->isEmpty()) {
            $this->progress('Sin procesos nuevos. Sondeo completo.', 'success');
            if (! $this->option('dry-run')) {
                Setting::set('last_polled_at', $to->format('Y-m-d H:i:s'));
                Setting::set('poll_status', 'idle');
                $this->cleanup($api);
            }

            return self::SUCCESS;
        }

        // Load filters once
        $minEnabled = Setting::get('min_amount_filter') === '1';
        $minValue = (float) (Setting::get('min_amount_value') ?? 0);
        $maxEnabled = Setting::get('max_amount_filter') === '1';
        $maxValue = (float) (Setting::get('max_amount_value') ?? 0);
        $excluded = json_decode(Setting::get('excluded_modalities', '[]'), true) ?: [];
        $openDeadlineEnabled = Setting::get('open_deadline_filter') === '1';

        $this->progress('Obteniendo detalles de '.$newMatches->count().' proceso(s)...', 'info');

        $notified = 0;

        foreach ($newMatches as $processCode => $matchedRubros) {
            try {
                $process = $api->fetchProcessByCode($processCode);
            } catch (DgcpApiException $e) {
                $this->progress("Advertencia: no se pudo obtener detalles de {$processCode}: {$e->getMessage()}", 'warn');
                $process = null;
            }

            // Apply filters
            $amount = $this->parseAmount($process['monto_estimado'] ?? null);
            $modality = $process['modalidad'] ?? null;

            if ($minEnabled && $amount !== null && $amount < $minValue) {
                $this->progress("[FILTRADO] {$processCode} — monto {$amount} por debajo del mínimo {$minValue}", 'warn');

                continue;
            }

            if ($maxEnabled && $maxValue > 0 && $amount !== null && $amount > $maxValue) {
                $this->progress("[FILTRADO] {$processCode} — monto {$amount} por encima del máximo {$maxValue}", 'warn');

                continue;
            }

            if ($modality && in_array($modality, $excluded)) {
                $this->progress("[FILTRADO] {$processCode} — modalidad '{$modality}' excluida", 'warn');

                continue;
            }

            if ($openDeadlineEnabled) {
                $deadline = $this->parseDate($process['fecha_fin_recepcion_ofertas'] ?? null);
                if ($deadline !== null && $deadline < new \DateTime) {
                    $this->progress("[FILTRADO] {$processCode} — plazo vencido ({$deadline->format('Y-m-d H:i')})", 'warn');

                    continue;
                }
            }

            $firstArticle = $firstArticleByProcess->get($processCode, []);

            if ($this->option('dry-run')) {
                $this->progress("[DRY] {$processCode} — ".$matchedRubros->pluck('name')->join(', '), 'match');

                continue;
            }

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
            ]);

            SendBidNotification::dispatch($bid);
            $notified++;

            $this->progress("[MATCH] {$processCode} — ".$matchedRubros->pluck('name')->join(', '), 'match');
        }

        if (! $this->option('dry-run')) {
            Setting::set('last_polled_at', $to->format('Y-m-d H:i:s'));
            Setting::set('poll_status', 'idle');
            $this->cleanup($api);
        }

        $summary = "Sondeo completo. Coincidencias: {$matchesByProcess->count()} | Nuevos: {$newMatches->count()} | Notificados: {$notified}";
        $this->progress($summary, 'success');
        Log::info("[SECP] {$summary}");

        return self::SUCCESS;
    }

    private function progress(string $message, string $type = 'info'): void
    {
        $this->line($message);

        $log = json_decode(Setting::get('poll_log', '[]'), true) ?: [];
        $log[] = ['time' => now()->format('H:i:s'), 'msg' => $message, 'type' => $type];

        if (count($log) > 300) {
            $log = array_slice($log, -300);
        }

        Setting::set('poll_log', json_encode($log));
    }

    private function cleanup(DgcpApiClient $api): void
    {
        $closedStatuses = [
            'Cancelado',
            'Proceso adjudicado y celebrado',
            'Proceso con etapa cerrada',
            'Proceso desierto',
            'Sobres abiertos o aperturados',
            'Sobres estan abriendose',
        ];

        // Refresh status for all stored bids — DGCP may have changed them since we saved
        foreach (Bid::all() as $bid) {
            try {
                $process = $api->fetchProcessByCode($bid->process_code);
                if ($process && isset($process['estado_proceso'])) {
                    $bid->update(['status' => $process['estado_proceso']]);
                }
            } catch (\Throwable) {
                // Keep existing status if API call fails
            }
        }

        $deleted = Bid::where(function ($q) {
            $q->whereNotNull('tender_deadline')
                ->where('tender_deadline', '<', now());
        })
            ->orWhereIn('status', $closedStatuses)
            ->delete();

        if ($deleted > 0) {
            $this->progress("Limpieza: {$deleted} convocatoria(s) eliminada(s) por plazo vencido o proceso cerrado.", 'info');
            Log::info("[SECP] Cleanup removed {$deleted} expired/closed bids.");
        }
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
