<?php

namespace App\Console\Commands;

use App\Exceptions\DgcpApiException;
use App\Models\Provider;
use App\Services\DgcpApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncProvidersCommand extends Command
{
    protected $signature = 'secp:sync-providers
        {--max-pages=200 : Max pages to fetch}';

    protected $description = 'Sync provider directory from the DGCP API';

    public function handle(DgcpApiClient $api): int
    {
        $maxPages = (int) $this->option('max-pages');

        $this->info("Syncing providers (max {$maxPages} pages)...");

        try {
            $providers = $api->fetchProviders(maxPages: $maxPages);
        } catch (DgcpApiException $e) {
            $this->error("Failed to fetch providers: {$e->getMessage()}");
            Log::error("[SyncProviders] Fetch failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("  → {$providers->count()} provider(s) fetched.");

        $new = 0;
        $updated = 0;

        foreach ($providers as $item) {
            $rpe = (string) ($item['rpe'] ?? null);
            if (! $rpe) {
                continue;
            }

            $data = [
                'razon_social' => $item['razon_social'] ?? null,
                'rnc' => $item['numero_documento'] ?? null,
                'status' => $item['estado'] ?? null,
                'tipo_persona' => $item['tipo_persona'] ?? null,
                'is_mipyme' => strtolower($item['es_mipyme'] ?? 'no') === 'si',
                'classification' => $item['clasificacion'] ?? null,
                'phone' => $item['telefono_comercial'] ?? $item['celular_comercial'] ?? null,
                'email' => $item['correo_comercial'] ?? null,
                'address' => $item['direccion'] ?? null,
                'province' => $item['provincia'] ?? null,
                'municipality' => $item['municipio'] ?? null,
                'contact_name' => $item['contacto'] ?? null,
                'contact_position' => $item['posicion_contacto'] ?? null,
                'raw_data' => $item,
            ];

            $existing = Provider::where('rpe', $rpe)->first();

            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                $data['rpe'] = $rpe;
                Provider::create($data);
                $new++;
            }
        }

        $summary = "Sync complete. Providers: {$new} new, {$updated} updated.";
        $this->info($summary);
        Log::info("[SyncProviders] {$summary}");

        return self::SUCCESS;
    }
}
