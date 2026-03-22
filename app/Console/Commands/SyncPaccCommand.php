<?php

namespace App\Console\Commands;

use App\Models\PaccAcquisition;
use App\Models\PaccPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncPaccCommand extends Command
{
    protected $signature = 'secp:sync-pacc
        {--year= : Year to sync (default: current year)}
        {--max-pages=0 : Max pages to fetch (0 = unlimited, 1000 records/page)}';

    protected $description = 'Sync PACC (annual purchase plans) and acquisitions from the DGCP API';

    private const BASE_URL = 'https://datosabiertos.dgcp.gob.do/api-dgcp/v1';

    private const PAGE_SIZE = 1000;

    private const UPSERT_CHUNK = 200;

    public function handle(): int
    {
        $year = (int) ($this->option('year') ?: date('Y'));
        $maxPages = (int) $this->option('max-pages');

        $this->info("Syncing PACC data for year {$year}...");

        $this->syncPlans($year, $maxPages);
        $this->syncAcquisitions($year, $maxPages);

        return self::SUCCESS;
    }

    private function syncPlans(int $year, int $maxPages): void
    {
        $this->info('=== Syncing PACC plans ===');

        $first = $this->fetchPage('/pacc', 0, ['año' => $year]);
        if (! $first || empty($first['payload']['content'] ?? [])) {
            $this->warn('  No plans returned from API.');

            return;
        }

        $totalPages = $first['pages'] ?? 1;
        if ($maxPages > 0) {
            $totalPages = min($totalPages, $maxPages);
        }
        $totalRecords = $first['totalResults'] ?? ($totalPages * self::PAGE_SIZE);
        $this->info("  {$totalRecords} plans across {$totalPages} pages.");

        $bar = $this->output->createProgressBar($totalPages);
        $bar->setFormat('  %current%/%max% pages [%bar%] %percent:3s%% — %elapsed:6s% / ~%estimated:-6s% — %message%');
        $bar->setMessage('starting...');
        $bar->start();

        $startTime = microtime(true);
        $page = 0;
        $upserted = 0;

        do {
            $json = $page === 0 ? $first : $this->fetchPage('/pacc', $page, ['año' => $year]);
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
            foreach ($items as $plan) {
                $uidPacc = $plan['uid_pacc'] ?? $plan['id_pacc'] ?? null;
                if (! $uidPacc) {
                    continue;
                }

                $rows[] = [
                    'uid_pacc' => mb_substr($uidPacc, 0, 100),
                    'institution_code' => mb_substr((string) ($plan['codigo_unidad_compra'] ?? ''), 0, 50),
                    'institution_name' => mb_substr($plan['unidad_compra'] ?? '', 0, 255) ?: null,
                    'period' => (int) ($plan['periodo'] ?? $year),
                    'version' => mb_substr($plan['version'] ?? '', 0, 50) ?: null,
                    'responsible' => mb_substr($plan['responsable'] ?? '', 0, 255) ?: null,
                    'email' => mb_substr($plan['correo_responsable'] ?? '', 0, 255) ?: null,
                    'url' => mb_substr($plan['url'] ?? '', 0, 500) ?: null,
                    'raw_data' => json_encode($plan),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($rows)) {
                foreach (array_chunk($rows, self::UPSERT_CHUNK) as $chunk) {
                    PaccPlan::upsert($chunk, ['uid_pacc'], [
                        'institution_code', 'institution_name', 'period', 'version',
                        'responsible', 'email', 'url', 'raw_data', 'updated_at',
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
        $summary = "Plans: {$upserted} upserted — {$elapsed}s";
        $this->info("  {$summary}");
        Log::info("[SyncPacc] {$summary}");
    }

    private function syncAcquisitions(int $year, int $maxPages): void
    {
        $this->info('=== Syncing PACC acquisitions ===');

        $first = $this->fetchPage('/pacc/adquisiciones', 0, ['año' => $year]);
        if (! $first || empty($first['payload']['content'] ?? [])) {
            $this->warn('  No acquisitions returned from API.');

            return;
        }

        $totalPages = $first['pages'] ?? 1;
        if ($maxPages > 0) {
            $totalPages = min($totalPages, $maxPages);
        }
        $totalRecords = $first['totalResults'] ?? ($totalPages * self::PAGE_SIZE);
        $this->info("  {$totalRecords} acquisitions across {$totalPages} pages.");

        $bar = $this->output->createProgressBar($totalPages);
        $bar->setFormat('  %current%/%max% pages [%bar%] %percent:3s%% — %elapsed:6s% / ~%estimated:-6s% — %message%');
        $bar->setMessage('starting...');
        $bar->start();

        $startTime = microtime(true);
        $page = 0;
        $upserted = 0;

        do {
            $json = $page === 0 ? $first : $this->fetchPage('/pacc/adquisiciones', $page, ['año' => $year]);
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
                $unspsc = $this->parseUnspscFromDescription($item['descripcion'] ?? '');

                $rows[] = [
                    'api_hash' => $hash,
                    'id_adquisicion' => mb_substr($item['id_adquisicion'] ?? '', 0, 100) ?: null,
                    'uid_pacc' => mb_substr($item['uid_pacc'] ?? '', 0, 100) ?: null,
                    'institution_code' => mb_substr((string) ($item['codigo_unidad_compra'] ?? ''), 0, 50),
                    'institution_name' => mb_substr($item['unidad_compra'] ?? '', 0, 255) ?: null,
                    'description' => $item['descripcion'] ?? null,
                    'purpose' => $item['finalidad'] ?? null,
                    'start_date' => $this->parseDate($item['fecha_inicio_proceso_compra'] ?? null),
                    'object_type' => mb_substr($item['objeto_adquisicion'] ?? '', 0, 100) ?: null,
                    'estimated_amount' => $this->parseDecimal($item['valor_presupuestado'] ?? null),
                    'currency' => 'DOP',
                    'modality' => mb_substr($item['modalidad_adquisicion'] ?? '', 0, 100) ?: null,
                    'mipymes' => strtolower($item['dirigido_mipymes'] ?? 'no') === 'si',
                    'mipymes_mujeres' => strtolower($item['dirigido_mipymes_mujeres'] ?? 'no') === 'si',
                    'unspsc_familia' => $unspsc['unspsc_familia'] ? mb_substr($unspsc['unspsc_familia'], 0, 20) : null,
                    'unspsc_clase' => $unspsc['unspsc_clase'] ? mb_substr($unspsc['unspsc_clase'], 0, 20) : null,
                    'unspsc_subclase' => $unspsc['unspsc_subclase'] ? mb_substr($unspsc['unspsc_subclase'], 0, 20) : null,
                    'unspsc_description' => $unspsc['unspsc_description'] ? mb_substr($unspsc['unspsc_description'], 0, 500) : null,
                    'status' => mb_substr($item['estado'] ?? '', 0, 50) ?: null,
                    'raw_data' => json_encode($item),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($rows)) {
                foreach (array_chunk($rows, self::UPSERT_CHUNK) as $chunk) {
                    PaccAcquisition::upsert($chunk, ['api_hash'], [
                        'id_adquisicion', 'uid_pacc', 'institution_code', 'institution_name',
                        'description', 'purpose', 'start_date', 'object_type', 'estimated_amount',
                        'currency', 'modality', 'mipymes', 'mipymes_mujeres',
                        'unspsc_familia', 'unspsc_clase', 'unspsc_subclase', 'unspsc_description',
                        'status', 'raw_data', 'updated_at',
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
        $summary = "Acquisitions: {$upserted} upserted — {$elapsed}s";
        $this->info("  {$summary}");
        Log::info("[SyncPacc] {$summary}");
    }

    private function fetchPage(string $endpoint, int $page, array $extra = [], int $maxRetries = 3): ?array
    {
        $params = array_merge($extra, [
            'page' => $page,
            'limit' => self::PAGE_SIZE,
        ]);

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
            $item['uid_pacc'] ?? '',
            $item['id_adquisicion'] ?? '',
            $item['descripcion'] ?? '',
            $item['codigo_unidad_compra'] ?? '',
            $item['version'] ?? '',
        ]));
    }

    private function parseUnspscFromDescription(string $desc): array
    {
        $result = [
            'unspsc_familia' => null,
            'unspsc_clase' => null,
            'unspsc_subclase' => null,
            'unspsc_description' => null,
        ];

        if (preg_match('/^(\d{4,8})\s*-\s*(.+)/', $desc, $m)) {
            $rawLen = strlen($m[1]);
            $code = str_pad($m[1], 8, '0');
            $result['unspsc_familia'] = substr($code, 0, 4).'0000';
            if ($rawLen >= 6) {
                $result['unspsc_clase'] = substr($code, 0, 6).'00';
            }
            if ($rawLen >= 8) {
                $result['unspsc_subclase'] = $code;
            }
            $result['unspsc_description'] = trim(preg_replace('/\s*\(T\d+\)\s*$/', '', $m[2]));
        }

        return $result;
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
