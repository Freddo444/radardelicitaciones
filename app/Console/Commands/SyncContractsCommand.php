<?php

namespace App\Console\Commands;

use App\Models\AwardedArticle;
use App\Models\Contract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncContractsCommand extends Command
{
    protected $signature = 'secp:sync-contracts
        {--max-pages=0 : Max pages to fetch (0 = unlimited, 1000 records/page)}
        {--only-articles : Only sync awarded articles, skip contracts}
        {--only-contracts : Only sync contracts, skip articles}';

    protected $description = 'Sync all contracts and awarded articles from the DGCP API';

    private const BASE_URL = 'https://datosabiertos.dgcp.gob.do/api-dgcp/v1';

    private const PAGE_SIZE = 1000;

    private const UPSERT_CHUNK = 200;

    public function handle(): int
    {
        $maxPages = (int) $this->option('max-pages');

        if (! $this->option('only-articles')) {
            $this->syncContracts($maxPages);
        }

        if (! $this->option('only-contracts')) {
            $this->syncArticles($maxPages);
        }

        return self::SUCCESS;
    }

    private function syncContracts(int $maxPages): void
    {
        $this->info('=== Syncing contracts ===');

        $first = $this->fetchPage('/contratos', 0);
        if (! $first || empty($first['payload']['content'] ?? [])) {
            $this->warn('  No contracts returned from API.');

            return;
        }

        $totalPages = $first['pages'] ?? 1;
        if ($maxPages > 0) {
            $totalPages = min($totalPages, $maxPages);
        }
        $totalRecords = $first['totalResults'] ?? ($totalPages * self::PAGE_SIZE);
        $this->info("  {$totalRecords} contracts across {$totalPages} pages.");

        $bar = $this->output->createProgressBar($totalPages);
        $bar->setFormat('  %current%/%max% pages [%bar%] %percent:3s%% — %elapsed:6s% / ~%estimated:-6s% — %message%');
        $bar->setMessage('starting...');
        $bar->start();

        $startTime = microtime(true);
        $page = 0;
        $upserted = 0;

        do {
            $json = $page === 0 ? $first : $this->fetchPage('/contratos', $page);
            if (! $json) {
                $page++;
                $bar->advance();

                continue;
            }

            $items = $json['payload']['content'] ?? [];
            if (empty($items)) {
                break;
            }

            $rows = [];
            $now = now();
            foreach ($items as $item) {
                $code = $item['codigo_contrato'] ?? null;
                if (! $code) {
                    continue;
                }

                $rows[] = [
                    'contract_code' => mb_substr($code, 0, 100),
                    'process_code' => mb_substr($item['codigo_proceso'] ?? '', 0, 80) ?: null,
                    'status' => mb_substr($item['estado_contrato'] ?? '', 0, 50) ?: null,
                    'provider_name' => mb_substr($item['razon_social'] ?? '', 0, 255) ?: null,
                    'provider_rpe' => mb_substr((string) ($item['rpe'] ?? ''), 0, 50),
                    'institution_name' => mb_substr($item['unidad_compra'] ?? '', 0, 255) ?: null,
                    'institution_code' => mb_substr((string) ($item['codigo_unidad_compra'] ?? ''), 0, 50),
                    'amount' => $this->parseDecimal($item['valor_contratado'] ?? null),
                    'currency' => mb_substr($item['divisa'] ?? 'DOP', 0, 10),
                    'payment_method' => mb_substr($item['metodo_pago'] ?? '', 0, 100) ?: null,
                    'payment_terms' => mb_substr($item['plazo_pago_factura'] ?? '', 0, 255) ?: null,
                    'description' => $item['descripcion'] ?? null,
                    'award_date' => $this->parseDate($item['fecha_adjudicacion'] ?? null),
                    'contract_date' => $this->parseDate($item['fecha_creacion_contrato'] ?? null),
                    'url' => mb_substr($item['url_contrato'] ?? '', 0, 500) ?: null,
                    'raw_data' => json_encode($item),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($rows)) {
                foreach (array_chunk($rows, self::UPSERT_CHUNK) as $chunk) {
                    Contract::upsert($chunk, ['contract_code'], [
                        'process_code', 'status', 'provider_name', 'provider_rpe',
                        'institution_name', 'institution_code', 'amount', 'currency',
                        'payment_method', 'payment_terms', 'description',
                        'award_date', 'contract_date', 'url', 'raw_data', 'updated_at',
                    ]);
                }
                $upserted += count($rows);
            }

            $page++;
            $rate = $page / max(microtime(true) - $startTime, 0.1);
            $bar->setMessage("{$upserted} upserted — ".number_format($rate * self::PAGE_SIZE, 0).' rec/min');
            $bar->advance();
        } while ($page < $totalPages);

        $bar->finish();
        $this->newLine();

        $elapsed = round(microtime(true) - $startTime, 1);
        $summary = "Contracts: {$upserted} upserted — {$elapsed}s";
        $this->info("  {$summary}");
        Log::info("[SyncContracts] {$summary}");
    }

    private function syncArticles(int $maxPages): void
    {
        $this->info('=== Syncing awarded articles ===');

        // Build in-memory lookup for provider/institution enrichment
        $this->info('  Loading contract cache...');
        $contractCache = DB::table('contracts')
            ->select('contract_code', 'provider_name', 'provider_rpe', 'institution_name', 'institution_code', 'currency')
            ->get()
            ->keyBy('contract_code');
        $this->info("  {$contractCache->count()} contracts cached.");

        $first = $this->fetchPage('/contratos/articulos', 0);
        if (! $first || empty($first['payload']['content'] ?? [])) {
            $this->warn('  No articles returned from API.');

            return;
        }

        $totalPages = $first['pages'] ?? 1;
        if ($maxPages > 0) {
            $totalPages = min($totalPages, $maxPages);
        }
        $totalRecords = $first['totalResults'] ?? ($totalPages * self::PAGE_SIZE);
        $this->info("  {$totalRecords} articles across {$totalPages} pages.");

        $bar = $this->output->createProgressBar($totalPages);
        $bar->setFormat('  %current%/%max% pages [%bar%] %percent:3s%% — %elapsed:6s% / ~%estimated:-6s% — %message%');
        $bar->setMessage('starting...');
        $bar->start();

        $startTime = microtime(true);
        $page = 0;
        $upserted = 0;

        do {
            $json = $page === 0 ? $first : $this->fetchPage('/contratos/articulos', $page);
            if (! $json) {
                $page++;
                $bar->advance();

                continue;
            }

            $items = $json['payload']['content'] ?? [];
            if (empty($items)) {
                break;
            }

            $rows = [];
            $now = now();
            foreach ($items as $item) {
                $hash = $this->computeHash($item);
                $contractCode = $item['codigo_contrato'] ?? '';
                $contract = $contractCache->get($contractCode);

                $rows[] = [
                    'api_hash' => $hash,
                    'contract_code' => mb_substr($item['codigo_contrato'] ?? '', 0, 80) ?: null,
                    'process_code' => mb_substr($item['codigo_proceso'] ?? '', 0, 80) ?: null,
                    'unspsc_familia' => mb_substr($item['familia'] ?? '', 0, 20) ?: null,
                    'unspsc_clase' => mb_substr($item['clase'] ?? '', 0, 20) ?: null,
                    'unspsc_subclase' => mb_substr($item['subclase'] ?? '', 0, 20) ?: null,
                    'unspsc_description' => mb_substr($item['descripcion_articulo'] ?? '', 0, 500) ?: null,
                    'description' => $item['descripcion_usuario'] ?? $item['descripcion_articulo'] ?? null,
                    'unit_measure' => mb_substr($item['unidad_medida'] ?? '', 0, 100) ?: null,
                    'quantity' => $this->parseDecimal($item['cantidad'] ?? null),
                    'unit_price' => $this->parseDecimal($item['precio_unitario'] ?? null),
                    'total' => $this->parseDecimal($item['costo_total'] ?? null),
                    'currency' => mb_substr($contract->currency ?? 'DOP', 0, 10),
                    'provider_name' => mb_substr($contract->provider_name ?? '', 0, 255) ?: null,
                    'provider_rpe' => mb_substr($contract->provider_rpe ?? '', 0, 50),
                    'institution_name' => mb_substr($contract->institution_name ?? '', 0, 255) ?: null,
                    'institution_code' => mb_substr($contract->institution_code ?? '', 0, 50),
                    'award_date' => $this->parseDate($item['fecha_creacion_contrato'] ?? null),
                    'raw_data' => json_encode($item),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($rows)) {
                foreach (array_chunk($rows, self::UPSERT_CHUNK) as $chunk) {
                    AwardedArticle::upsert($chunk, ['api_hash'], [
                        'contract_code', 'process_code', 'unspsc_familia', 'unspsc_clase',
                        'unspsc_subclase', 'unspsc_description', 'description', 'unit_measure',
                        'quantity', 'unit_price', 'total', 'currency', 'provider_name',
                        'provider_rpe', 'institution_name', 'institution_code',
                        'award_date', 'raw_data', 'updated_at',
                    ]);
                }
                $upserted += count($rows);
            }

            $page++;
            $rate = $page / max(microtime(true) - $startTime, 0.1);
            $bar->setMessage("{$upserted} upserted — ".number_format($rate * self::PAGE_SIZE, 0).' rec/min');
            $bar->advance();
        } while ($page < $totalPages);

        $bar->finish();
        $this->newLine();

        $elapsed = round(microtime(true) - $startTime, 1);
        $summary = "Articles: {$upserted} upserted — {$elapsed}s";
        $this->info("  {$summary}");
        Log::info("[SyncContracts] {$summary}");
    }

    private function fetchPage(string $endpoint, int $page, int $maxRetries = 3): ?array
    {
        $params = ['page' => $page, 'limit' => self::PAGE_SIZE];

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::timeout(60)->get(self::BASE_URL.$endpoint, $params);

                if ($response->status() === 429) {
                    $this->warn('  Rate limited, sleeping 65s...');
                    sleep(65);

                    continue;
                }

                if ($response->serverError()) {
                    $this->warn("  HTTP {$response->status()} on {$endpoint} page {$page} (attempt {$attempt}/{$maxRetries})");
                    sleep(5 * $attempt);

                    continue;
                }

                if ($response->failed()) {
                    $this->warn("  HTTP {$response->status()} on {$endpoint} page {$page}");

                    return null;
                }

                return $response->json();
            } catch (\Throwable $e) {
                $this->warn("  Error on {$endpoint} page {$page} (attempt {$attempt}/{$maxRetries}): {$e->getMessage()}");
                if ($attempt < $maxRetries) {
                    sleep(5 * $attempt);
                }
            }
        }

        $this->warn("  Giving up on {$endpoint} page {$page} after {$maxRetries} attempts");

        return null;
    }

    private function computeHash(array $item): string
    {
        return hash('sha256', implode('|', [
            $item['codigo_contrato'] ?? '',
            $item['codigo_proceso'] ?? '',
            $item['descripcion_articulo'] ?? '',
            $item['subclase'] ?? '',
            $item['cantidad'] ?? '',
            $item['precio_unitario'] ?? '',
        ]));
    }

    private function parseDecimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }
        $cleaned = preg_replace('/[^\d.]/', '', (string) $value);

        return $cleaned !== '' ? (float) $cleaned : 0;
    }

    private function parseDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        try {
            return (new \DateTime($value))->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }
}
