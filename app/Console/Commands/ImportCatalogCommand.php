<?php

namespace App\Console\Commands;

use App\Models\CatalogItem;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportCatalogCommand extends Command
{
    protected $signature = 'secp:import-catalog
                            {--attempts=4 : Tries per page before giving up}';

    protected $description = 'Import UNSPSC catalog from DGCP API into local database';

    private const API_URL = 'https://datosabiertos.dgcp.gob.do/api-dgcp/v1/catalogo';

    private const LIMIT = 1000;

    public function handle(): int
    {
        $this->info('Importing UNSPSC catalog...');

        $page = 1;
        $total = 0;

        do {
            $response = $this->fetchPage($page);

            if ($response === null) {
                // A page we cannot read means an incomplete catalog, and the
                // rubros a company can pick come straight from this table.
                // Earlier pages stay imported — upsert, nothing is deleted —
                // but the run is a failure and the stored count and timestamp
                // are left alone rather than recording a partial import as the
                // current state of the catalog.
                $this->error("No se pudo leer la página {$page} del catálogo. ".
                    "Importadas {$total} subclases de las páginas anteriores; el catálogo queda incompleto.");
                Log::error('[SECP] Catalog import incomplete', [
                    'failed_page' => $page,
                    'imported_before_failure' => $total,
                ]);

                return self::FAILURE;
            }

            $items = $response->json('payload.content', []);

            if (! is_array($items) || $items === []) {
                break;
            }

            $batch = [];
            foreach ($items as $item) {
                if (! is_array($item) || empty($item['subclase'])) {
                    continue;
                }

                // Every column is NOT NULL, so a missing key becomes '' the
                // way the descriptions already did, rather than a null that
                // fails the insert and takes the whole page with it.
                $batch[] = [
                    'subclase' => (string) $item['subclase'],
                    'descripcion_subclase' => trim((string) ($item['descripcion_subclase'] ?? '')),
                    'clase' => (string) ($item['clase'] ?? ''),
                    'descripcion_clase' => trim((string) ($item['descripcion_clase'] ?? '')),
                    'familia' => (string) ($item['familia'] ?? ''),
                    'descripcion_familia' => trim((string) ($item['descripcion_familia'] ?? '')),
                    'segmento' => (string) ($item['segmento'] ?? ''),
                    'descripcion_segmento' => trim((string) ($item['descripcion_segmento'] ?? '')),
                ];
            }

            foreach (array_chunk($batch, 500) as $chunk) {
                CatalogItem::upsert($chunk, ['subclase'], [
                    'descripcion_subclase', 'clase', 'descripcion_clase',
                    'familia', 'descripcion_familia', 'segmento', 'descripcion_segmento',
                ]);
            }

            $total += count($items);
            $this->line("  Page {$page}: {$total} records");
            $page++;

        } while (count($items) === self::LIMIT);

        Setting::set('catalog_last_imported_at', now()->toDateTimeString());
        Setting::set('catalog_item_count', (string) $total);

        $this->info("Done. {$total} catalog items imported.");
        Log::info("[SECP] Catalog import complete: {$total} items.");

        return self::SUCCESS;
    }

    /**
     * One page, retried on a timeout or a 5xx.
     *
     * The import is 17 sequential pages of 1,000 against an API that is slow
     * under load, and it used to let a single ConnectionException escape and
     * kill the whole run — on 2026-11-01 page 12 timed out and the catalog has
     * been a month stale since. A transient timeout should cost a retry, not
     * the month.
     */
    private function fetchPage(int $page): ?Response
    {
        $attempts = max(1, (int) $this->option('attempts'));

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = Http::timeout(60)->get(self::API_URL, [
                    'page' => $page,
                    'limit' => self::LIMIT,
                ]);

                if ($response->successful()) {
                    return $response;
                }

                // 4xx will not change on a retry; 5xx might.
                if ($response->status() < 500) {
                    $this->error("API error on page {$page}: ".$response->status());

                    return null;
                }

                $reason = 'HTTP '.$response->status();
            } catch (ConnectionException $e) {
                $reason = $e->getMessage();
            }

            if ($attempt < $attempts) {
                $wait = 5 * $attempt;
                $this->warn("  Página {$page} falló ({$reason}). Reintento {$attempt}/".($attempts - 1)." en {$wait}s.");

                if (! app()->runningUnitTests()) {
                    sleep($wait);
                }
            } else {
                $this->warn("  Página {$page} falló ({$reason}) tras {$attempts} intento(s).");
            }
        }

        return null;
    }
}
