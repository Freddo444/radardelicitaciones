<?php

namespace App\Console\Commands;

use App\Exceptions\DgcpApiException;
use App\Models\AwardedArticle;
use App\Models\Contract;
use App\Models\Rubro;
use App\Services\DgcpApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncContractsCommand extends Command
{
    protected $signature = 'secp:sync-contracts
        {--rubro= : Sync only a specific rubro code}
        {--max-pages=50 : Max pages to fetch per rubro}
        {--only-articles : Only sync awarded articles, skip contracts}
        {--only-contracts : Only sync contracts, skip articles}';

    protected $description = 'Sync contracts and awarded articles from the DGCP API';

    public function handle(DgcpApiClient $api): int
    {
        $maxPages = (int) $this->option('max-pages');
        $onlyArticles = $this->option('only-articles');
        $onlyContracts = $this->option('only-contracts');

        if (! $onlyArticles) {
            $this->syncContracts($api, $maxPages);
        }

        if (! $onlyContracts) {
            $this->syncArticles($api, $maxPages);
        }

        return self::SUCCESS;
    }

    private function syncContracts(DgcpApiClient $api, int $maxPages): void
    {
        $this->info('=== Syncing contracts ===');

        try {
            $contracts = $api->fetchContractsPaginated(maxPages: $maxPages);
        } catch (DgcpApiException $e) {
            $this->error("Failed to fetch contracts: {$e->getMessage()}");
            Log::error("[SyncContracts] Contracts fetch failed: {$e->getMessage()}");

            return;
        }

        $this->info("  → {$contracts->count()} contract(s) fetched.");

        $new = 0;
        $updated = 0;

        foreach ($contracts as $item) {
            $contractCode = $item['codigo_contrato'] ?? $item['contrato'] ?? null;
            if (! $contractCode) {
                continue;
            }

            $data = [
                'process_code' => $item['codigo_proceso'] ?? null,
                'status' => $item['estado_contrato'] ?? null,
                'provider_name' => $item['razon_social'] ?? null,
                'provider_rpe' => (string) ($item['rpe'] ?? ''),
                'institution_name' => $item['unidad_compra'] ?? null,
                'institution_code' => (string) ($item['codigo_unidad_compra'] ?? ''),
                'amount' => $this->parseDecimal($item['valor_contratado'] ?? null),
                'currency' => $item['divisa'] ?? 'DOP',
                'payment_method' => $item['metodo_pago'] ?? null,
                'payment_terms' => $item['plazo_pago_factura'] ?? null,
                'description' => $item['descripcion'] ?? null,
                'award_date' => $this->parseDate($item['fecha_adjudicacion'] ?? null),
                'contract_date' => $this->parseDate($item['fecha_creacion_contrato'] ?? null),
                'url' => $item['url_contrato'] ?? null,
                'raw_data' => $item,
            ];

            $existing = Contract::where('contract_code', $contractCode)->first();

            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                $data['contract_code'] = $contractCode;
                Contract::create($data);
                $new++;
            }
        }

        $summary = "Contracts: {$new} new, {$updated} updated.";
        $this->info($summary);
        Log::info("[SyncContracts] {$summary}");
    }

    private function syncArticles(DgcpApiClient $api, int $maxPages): void
    {
        $this->info('=== Syncing awarded articles ===');

        if ($rubroCode = $this->option('rubro')) {
            $rubros = Rubro::where('code', $rubroCode)->get();
            if ($rubros->isEmpty()) {
                $this->error("Rubro '{$rubroCode}' not found.");

                return;
            }
        } else {
            $rubros = Rubro::where('active', true)->get();
        }

        if ($rubros->isEmpty()) {
            $this->warn('No active rubros to sync articles.');

            return;
        }

        $this->info("Syncing articles for {$rubros->count()} rubro(s), max {$maxPages} pages each...");

        $totalNew = 0;
        $totalUpdated = 0;

        foreach ($rubros as $i => $rubro) {
            $idx = $i + 1;
            $this->info("[{$idx}/{$rubros->count()}] {$rubro->code} — {$rubro->name} ({$rubro->level})");

            try {
                $articles = $api->fetchContractArticlesByRubro($rubro->code, $rubro->level, $maxPages);
            } catch (DgcpApiException $e) {
                $this->warn("  Error: {$e->getMessage()}");
                Log::warning("[SyncContracts] Failed for {$rubro->code}: {$e->getMessage()}");

                continue;
            }

            $this->info("  → {$articles->count()} article(s) fetched.");

            $new = 0;
            $updated = 0;

            foreach ($articles as $item) {
                $hash = $this->computeArticleHash($item);

                $data = [
                    'contract_code' => $item['codigo_contrato'] ?? null,
                    'process_code' => $item['codigo_proceso'] ?? null,
                    'unspsc_familia' => $item['familia'] ?? null,
                    'unspsc_clase' => $item['clase'] ?? null,
                    'unspsc_subclase' => $item['subclase'] ?? null,
                    'unspsc_description' => $item['descripcion_articulo'] ?? null,
                    'description' => $item['descripcion_usuario'] ?? $item['descripcion_articulo'] ?? null,
                    'unit_measure' => $item['unidad_medida'] ?? null,
                    'quantity' => $this->parseDecimal($item['cantidad'] ?? null),
                    'unit_price' => $this->parseDecimal($item['precio_unitario'] ?? null),
                    'total' => $this->parseDecimal($item['costo_total'] ?? null),
                    'currency' => 'DOP',
                    'provider_name' => null,
                    'provider_rpe' => '',
                    'institution_name' => null,
                    'institution_code' => '',
                    'award_date' => $this->parseDate($item['fecha_creacion_contrato'] ?? null),
                    'raw_data' => $item,
                ];

                $existing = AwardedArticle::where('api_hash', $hash)->first();

                if ($existing) {
                    $existing->update($data);
                    $updated++;
                } else {
                    $data['api_hash'] = $hash;
                    AwardedArticle::create($data);
                    $new++;
                }
            }

            $this->info("  → {$new} new, {$updated} updated.");
            $totalNew += $new;
            $totalUpdated += $updated;
        }

        $summary = "Articles: {$totalNew} new, {$totalUpdated} updated.";
        $this->info($summary);
        Log::info("[SyncContracts] {$summary}");
    }

    private function computeArticleHash(array $item): string
    {
        $key = implode('|', [
            $item['codigo_contrato'] ?? '',
            $item['codigo_proceso'] ?? '',
            $item['descripcion_articulo'] ?? '',
            $item['subclase'] ?? '',
            $item['cantidad'] ?? '',
            $item['precio_unitario'] ?? '',
        ]);

        return hash('sha256', $key);
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
