<?php

namespace App\Console\Commands;

use App\Models\Provider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncProvidersCommand extends Command
{
    protected $signature = 'secp:sync-providers
        {--max-pages=0 : Max pages to fetch (0 = unlimited, 1000 records/page)}';

    protected $description = 'Sync provider directory from the DGCP API';

    private const BASE_URL = 'https://datosabiertos.dgcp.gob.do/api-dgcp/v1';

    private const PAGE_SIZE = 1000;

    private const UPSERT_CHUNK = 200;

    public function handle(): int
    {
        $maxPages = (int) $this->option('max-pages');

        $this->info('=== Syncing providers ===');

        $first = $this->fetchPage(0);
        if (! $first || empty($first['payload']['content'] ?? [])) {
            $this->warn('  No providers returned from API.');

            return self::FAILURE;
        }

        $totalPages = $first['pages'] ?? 1;
        if ($maxPages > 0) {
            $totalPages = min($totalPages, $maxPages);
        }
        $totalRecords = $first['totalResults'] ?? ($totalPages * self::PAGE_SIZE);
        $this->info("  {$totalRecords} providers across {$totalPages} pages.");

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
                $rpe = (string) ($item['rpe'] ?? '');
                if (! $rpe) {
                    continue;
                }

                $rows[] = [
                    'rpe' => mb_substr($rpe, 0, 50),
                    'razon_social' => mb_substr($item['razon_social'] ?? '', 0, 255) ?: null,
                    'rnc' => mb_substr($item['numero_documento'] ?? '', 0, 50) ?: null,
                    'status' => mb_substr($item['estado'] ?? '', 0, 50) ?: null,
                    'tipo_persona' => mb_substr($item['tipo_persona'] ?? '', 0, 50) ?: null,
                    'is_mipyme' => strtolower($item['es_mipyme'] ?? 'no') === 'si',
                    'classification' => mb_substr($item['clasificacion'] ?? '', 0, 100) ?: null,
                    'phone' => mb_substr($item['telefono_comercial'] ?? $item['celular_comercial'] ?? '', 0, 100) ?: null,
                    'email' => mb_substr($item['correo_comercial'] ?? '', 0, 255) ?: null,
                    'address' => $item['direccion'] ?? null,
                    'province' => mb_substr($item['provincia'] ?? '', 0, 100) ?: null,
                    'municipality' => mb_substr($item['municipio'] ?? '', 0, 100) ?: null,
                    'contact_name' => mb_substr($item['contacto'] ?? '', 0, 255) ?: null,
                    'contact_position' => mb_substr($item['posicion_contacto'] ?? '', 0, 255) ?: null,
                    'raw_data' => json_encode($item),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($rows)) {
                foreach (array_chunk($rows, self::UPSERT_CHUNK) as $chunk) {
                    Provider::upsert($chunk, ['rpe'], [
                        'razon_social', 'rnc', 'status', 'tipo_persona', 'is_mipyme',
                        'classification', 'phone', 'email', 'address', 'province',
                        'municipality', 'contact_name', 'contact_position',
                        'raw_data', 'updated_at',
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
        $summary = "Providers: {$upserted} upserted — {$elapsed}s";
        $this->info("  {$summary}");
        Log::info("[SyncProviders] {$summary}");

        return self::SUCCESS;
    }

    private function fetchPage(int $page, int $maxRetries = 3): ?array
    {
        $params = ['page' => $page, 'limit' => self::PAGE_SIZE];

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::timeout(60)->get(self::BASE_URL.'/proveedores', $params);

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
