<?php

namespace App\Console\Commands;

use App\Models\Institution;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncInstitutionsCommand extends Command
{
    protected $signature = 'secp:sync-institutions
        {--max-pages=0 : Max pages to fetch (0 = unlimited, 1000 records/page)}';

    protected $description = 'Sync purchasing units (institutions) from the DGCP API';

    private const BASE_URL = 'https://datosabiertos.dgcp.gob.do/api-dgcp/v1';

    private const PAGE_SIZE = 1000;

    private const UPSERT_CHUNK = 200;

    public function handle(): int
    {
        $maxPages = (int) $this->option('max-pages');

        $this->info('=== Syncing institutions ===');

        $first = $this->fetchPage(0);
        if (! $first || empty($first['payload']['content'] ?? [])) {
            $this->warn('  No institutions returned from API.');

            return self::FAILURE;
        }

        $totalPages = $first['pages'] ?? 1;
        if ($maxPages > 0) {
            $totalPages = min($totalPages, $maxPages);
        }
        $totalRecords = $first['totalResults'] ?? ($totalPages * self::PAGE_SIZE);
        $this->info("  {$totalRecords} institutions across {$totalPages} pages.");

        $bar = $this->output->createProgressBar($totalPages);
        $bar->setFormat('  %current%/%max% pages [%bar%] %percent:3s%% — %elapsed:6s% / ~%estimated:-6s% — %message%');
        $bar->setMessage('starting...');
        $bar->start();

        $startTime = microtime(true);
        $page = 0;
        $upserted = 0;

        do {
            $json = $page === 0 ? $first : $this->fetchPage($page);
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
                $code = (string) ($item['codigo_unidad_compra'] ?? $item['codigo'] ?? '');
                if (! $code) {
                    continue;
                }

                $rows[] = [
                    'code' => mb_substr($code, 0, 50),
                    'name' => mb_substr($item['unidad_compra'] ?? '', 0, 255) ?: null,
                    'acronym' => mb_substr($item['acronimo'] ?? '', 0, 50) ?: null,
                    'status' => mb_substr($item['estado'] ?? '', 0, 50) ?: null,
                    'address' => $item['direccion'] ?? null,
                    'phone' => mb_substr($item['telefono'] ?? '', 0, 100) ?: null,
                    'email' => mb_substr($item['correo'] ?? $item['correo_electronico'] ?? '', 0, 255) ?: null,
                    'notification_email' => mb_substr($item['correo_notificaciones'] ?? '', 0, 255) ?: null,
                    'raw_data' => json_encode($item),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($rows)) {
                foreach (array_chunk($rows, self::UPSERT_CHUNK) as $chunk) {
                    Institution::upsert($chunk, ['code'], [
                        'name', 'acronym', 'status', 'address', 'phone',
                        'email', 'notification_email', 'raw_data', 'updated_at',
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
        $summary = "Institutions: {$upserted} upserted — {$elapsed}s";
        $this->info("  {$summary}");
        Log::info("[SyncInstitutions] {$summary}");

        return self::SUCCESS;
    }

    private function fetchPage(int $page, int $maxRetries = 3): ?array
    {
        $params = ['page' => $page, 'limit' => self::PAGE_SIZE];

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::timeout(60)->get(self::BASE_URL.'/unidades_compra', $params);

                if ($response->status() === 429) {
                    $this->warn('  Rate limited, sleeping 65s...');
                    sleep(65);

                    continue;
                }

                if ($response->serverError()) {
                    $this->warn("  HTTP {$response->status()} on page {$page} (attempt {$attempt}/{$maxRetries})");
                    sleep(5 * $attempt);

                    continue;
                }

                if ($response->failed()) {
                    $this->warn("  HTTP {$response->status()} on page {$page}");

                    return null;
                }

                return $response->json();
            } catch (\Throwable $e) {
                $this->warn("  Error on page {$page} (attempt {$attempt}/{$maxRetries}): {$e->getMessage()}");
                if ($attempt < $maxRetries) {
                    sleep(5 * $attempt);
                }
            }
        }

        $this->warn("  Giving up on page {$page} after {$maxRetries} attempts");

        return null;
    }
}
