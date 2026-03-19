<?php

namespace App\Console\Commands;

use App\Exceptions\DgcpApiException;
use App\Models\Institution;
use App\Services\DgcpApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncInstitutionsCommand extends Command
{
    protected $signature = 'secp:sync-institutions
        {--max-pages=100 : Max pages to fetch}';

    protected $description = 'Sync purchasing units (institutions) from the DGCP API';

    public function handle(DgcpApiClient $api): int
    {
        $maxPages = (int) $this->option('max-pages');

        $this->info("Syncing institutions (max {$maxPages} pages)...");

        try {
            $items = $api->fetchInstitutions($maxPages);
        } catch (DgcpApiException $e) {
            $this->error("Failed to fetch institutions: {$e->getMessage()}");
            Log::error("[SyncInstitutions] Fetch failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("  → {$items->count()} institution(s) fetched.");

        $new = 0;
        $updated = 0;

        foreach ($items as $item) {
            $code = (string) ($item['codigo_unidad_compra'] ?? $item['codigo'] ?? null);
            if (! $code) {
                continue;
            }

            $data = [
                'name' => $item['nombre'] ?? $item['nombre_unidad_compra'] ?? null,
                'acronym' => $item['siglas'] ?? $item['acronimo'] ?? null,
                'status' => $item['estado'] ?? null,
                'address' => $item['direccion'] ?? null,
                'phone' => $item['telefono'] ?? null,
                'email' => $item['correo'] ?? $item['email'] ?? $item['correo_electronico'] ?? null,
                'notification_email' => $item['correo_notificacion'] ?? $item['email_notificacion'] ?? null,
                'raw_data' => $item,
            ];

            $existing = Institution::where('code', $code)->first();

            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                $data['code'] = $code;
                Institution::create($data);
                $new++;
            }
        }

        $summary = "Sync complete. Institutions: {$new} new, {$updated} updated.";
        $this->info($summary);
        Log::info("[SyncInstitutions] {$summary}");

        return self::SUCCESS;
    }
}
