<?php

namespace App\Console\Commands;

use App\Models\CatalogItem;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportCatalogCommand extends Command
{
    protected $signature   = 'secp:import-catalog';
    protected $description = 'Import UNSPSC catalog from DGCP API into local database';

    private const API_URL = 'https://datosabiertos.dgcp.gob.do/api-dgcp/v1/catalogo';
    private const LIMIT   = 1000;

    public function handle(): int
    {
        $this->info('Importing UNSPSC catalog...');

        $page  = 1;
        $total = 0;

        do {
            $response = Http::timeout(30)->get(self::API_URL, [
                'page'  => $page,
                'limit' => self::LIMIT,
            ]);

            if ($response->failed()) {
                $this->error("API error on page {$page}: " . $response->status());
                return self::FAILURE;
            }

            $items = $response->json('payload.content', []);

            if (empty($items)) {
                break;
            }

            $batch = [];
            foreach ($items as $item) {
                $batch[] = [
                    'subclase'             => $item['subclase'],
                    'descripcion_subclase' => trim($item['descripcion_subclase'] ?? ''),
                    'clase'                => $item['clase'],
                    'descripcion_clase'    => trim($item['descripcion_clase'] ?? ''),
                    'familia'              => $item['familia'],
                    'descripcion_familia'  => trim($item['descripcion_familia'] ?? ''),
                    'segmento'             => $item['segmento'],
                    'descripcion_segmento' => trim($item['descripcion_segmento'] ?? ''),
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
}
