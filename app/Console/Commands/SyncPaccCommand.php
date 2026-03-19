<?php

namespace App\Console\Commands;

use App\Exceptions\DgcpApiException;
use App\Models\PaccAcquisition;
use App\Models\PaccPlan;
use App\Services\DgcpApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPaccCommand extends Command
{
    protected $signature = 'secp:sync-pacc
        {--year= : Year to sync (default: current year)}
        {--max-pages=100 : Max pages to fetch for acquisitions}';

    protected $description = 'Sync PACC (annual purchase plans) and acquisitions from the DGCP API';

    public function handle(DgcpApiClient $api): int
    {
        $year = (int) ($this->option('year') ?: date('Y'));
        $maxPages = (int) $this->option('max-pages');

        $this->info("Syncing PACC data for year {$year}...");

        // Step 1: Fetch all PACC plans for the year
        $this->info('Fetching PACC plans...');
        try {
            $plans = $api->fetchPaccPlans($year);
        } catch (DgcpApiException $e) {
            $this->error("Failed to fetch PACC plans: {$e->getMessage()}");
            Log::error("[SyncPacc] Plans fetch failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("  → {$plans->count()} plan(s) fetched.");

        $newPlans = 0;
        $updatedPlans = 0;

        foreach ($plans as $plan) {
            $uidPacc = $plan['uid_pacc'] ?? $plan['id_pacc'] ?? null;
            if (! $uidPacc) {
                continue;
            }

            $data = [
                'institution_code' => (string) ($plan['codigo_unidad_compra'] ?? ''),
                'institution_name' => $plan['unidad_compra'] ?? null,
                'period' => (int) ($plan['periodo'] ?? $year),
                'version' => $plan['version'] ?? null,
                'responsible' => $plan['responsable'] ?? null,
                'email' => $plan['correo_responsable'] ?? null,
                'url' => $plan['url'] ?? null,
                'raw_data' => $plan,
            ];

            $existing = PaccPlan::where('uid_pacc', $uidPacc)->first();

            if ($existing) {
                $existing->update($data);
                $updatedPlans++;
            } else {
                $data['uid_pacc'] = $uidPacc;
                PaccPlan::create($data);
                $newPlans++;
            }
        }

        $this->info("  Plans: {$newPlans} new, {$updatedPlans} updated.");

        // Step 2: Fetch all acquisitions for the year
        $this->info('Fetching PACC acquisitions...');
        try {
            $acquisitions = $api->fetchPaccAcquisitions($year, maxPages: $maxPages);
        } catch (DgcpApiException $e) {
            $this->error("Failed to fetch acquisitions: {$e->getMessage()}");
            Log::error("[SyncPacc] Acquisitions fetch failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("  → {$acquisitions->count()} acquisition(s) fetched.");

        $newAcq = 0;
        $updatedAcq = 0;

        foreach ($acquisitions as $item) {
            $hash = $this->computeHash($item);

            $data = [
                'id_adquisicion' => $item['id_adquisicion'] ?? null,
                'uid_pacc' => $item['uid_pacc'] ?? null,
                'institution_code' => (string) ($item['codigo_unidad_compra'] ?? ''),
                'institution_name' => $item['unidad_compra'] ?? null,
                'description' => $item['descripcion'] ?? null,
                'purpose' => $item['finalidad'] ?? null,
                'start_date' => $this->parseDate($item['fecha_inicio_proceso_compra'] ?? null),
                'object_type' => $item['objeto_adquisicion'] ?? null,
                'estimated_amount' => $this->parseDecimal($item['valor_presupuestado'] ?? null),
                'currency' => 'DOP',
                'modality' => $item['modalidad_adquisicion'] ?? null,
                'mipymes' => strtolower($item['dirigido_mipymes'] ?? 'no') === 'si',
                'mipymes_mujeres' => strtolower($item['dirigido_mipymes_mujeres'] ?? 'no') === 'si',
                ...$this->parseUnspscFromDescription($item['descripcion'] ?? ''),
                'status' => $item['estado'] ?? null,
                'raw_data' => $item,
            ];

            $existing = PaccAcquisition::where('api_hash', $hash)->first();

            if ($existing) {
                $existing->update($data);
                $updatedAcq++;
            } else {
                $data['api_hash'] = $hash;
                PaccAcquisition::create($data);
                $newAcq++;
            }
        }

        $summary = "Sync complete. Plans: {$newPlans} new, {$updatedPlans} updated. Acquisitions: {$newAcq} new, {$updatedAcq} updated.";
        $this->info($summary);
        Log::info("[SyncPacc] {$summary}");

        return self::SUCCESS;
    }

    private function computeHash(array $item): string
    {
        $key = implode('|', [
            $item['uid_pacc'] ?? '',
            $item['id_adquisicion'] ?? '',
            $item['descripcion'] ?? '',
            $item['codigo_unidad_compra'] ?? '',
            $item['version'] ?? '',
        ]);

        return hash('sha256', $key);
    }

    /**
     * Extract UNSPSC code from description like "901016 - Servicios de banquetes (T1)".
     * Codes are 6-8 digits: segment(2) + family(2) + class(2) + subclass(2 optional).
     */
    private function parseUnspscFromDescription(string $desc): array
    {
        $result = [
            'unspsc_familia' => null,
            'unspsc_clase' => null,
            'unspsc_subclase' => null,
            'unspsc_description' => null,
        ];

        if (preg_match('/^(\d{6,8})\s*-\s*(.+)/', $desc, $m)) {
            $code = str_pad($m[1], 8, '0');
            $result['unspsc_familia'] = substr($code, 0, 4).'0000';
            $result['unspsc_clase'] = substr($code, 0, 6).'00';
            if (strlen($m[1]) >= 8) {
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
