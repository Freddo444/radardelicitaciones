<?php

namespace App\Console\Commands;

use App\Mail\RadarColdOutreachMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendRadarColdOutreachCommand extends Command
{
    protected $signature = 'secp:send-radar-cold-outreach
        {--csv=storage/app/proveedores-outreach-150.csv : Path to CSV (relative to project root or absolute)}
        {--tracking-url= : Target URL for the CTA (trial/register)}
        {--max=150 : Stop after this many successful sends}
        {--batch=20 : After this many successful sends, sleep --batch-sleep seconds}
        {--batch-sleep=1800 : Pause after each batch (default 1800 = 30 minutes)}
        {--min-delay=60 : Minimum seconds between successful sends (skipped rows do not wait)}
        {--max-delay=180 : Maximum seconds between successful sends}
        {--dry-run : Log recipients without sending}';

    protected $description = 'Send RadarColdOutreachMail from CSV with throttling: random delay between sends, long pause every N sends';

    public function handle(): int
    {
        $trackingUrl = (string) $this->option('tracking-url');
        if ($trackingUrl === '' || ! filter_var($trackingUrl, FILTER_VALIDATE_URL)) {
            $this->error('Provide a valid --tracking-url= (https://...)');

            return self::FAILURE;
        }

        $csvPath = $this->resolvePath((string) $this->option('csv'));
        if (! is_readable($csvPath)) {
            $this->error("CSV not readable: {$csvPath}");

            return self::FAILURE;
        }

        $max = max(1, (int) $this->option('max'));
        $batch = max(1, (int) $this->option('batch'));
        $batchSleep = max(0, (int) $this->option('batch-sleep'));
        $minDelay = max(0, (int) $this->option('min-delay'));
        $maxDelay = max($minDelay, (int) $this->option('max-delay'));
        $dryRun = (bool) $this->option('dry-run');

        $fh = fopen($csvPath, 'r');
        if ($fh === false) {
            $this->error("Cannot open CSV: {$csvPath}");

            return self::FAILURE;
        }

        $header = fgetcsv($fh);
        if ($header === false) {
            fclose($fh);
            $this->error('CSV is empty');

            return self::FAILURE;
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $emailKey = $this->pickColumn($header, ['email', 'correo']);
        $nameKey = $this->pickColumn($header, ['company_name', 'razon_social', 'nombre', 'empresa']);
        if ($emailKey === null) {
            fclose($fh);
            $this->error('CSV must include an email column (email or correo).');

            return self::FAILURE;
        }

        $skipped = 0;
        $failed = 0;
        $sent = 0;

        $this->info('CSV: '.$csvPath.($dryRun ? ' (dry-run)' : ''));
        $this->info("Target: {$max} successful send(s); batch {$batch} then {$batchSleep}s pause; delay {$minDelay}-{$maxDelay}s.");

        while (($row = fgetcsv($fh)) !== false) {
            if ($sent >= $max) {
                break;
            }

            $data = $this->rowToAssoc($header, $row);
            $email = strtolower(trim((string) ($data[$emailKey] ?? '')));
            $companyName = trim((string) ($data[$nameKey] ?? ''));
            if ($companyName === '') {
                $companyName = 'su empresa';
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                Log::info('[RadarColdOutreach] skipped_invalid_email', ['email' => $email]);
                $this->line("SKIP invalid email: {$email}");

                continue;
            }

            try {
                if ($dryRun) {
                    $this->line("[DRY] → {$email} ({$companyName})");
                } else {
                    Mail::to($email)->send(new RadarColdOutreachMail($companyName, $trackingUrl));
                }
                $sent++;
                Log::info('[RadarColdOutreach] sent', [
                    'email' => $email,
                    'company' => $companyName,
                    'count' => $sent,
                ]);
                $this->info("[{$sent}/{$max}] Sent → {$email}");
            } catch (\Throwable $e) {
                $failed++;
                Log::error('[RadarColdOutreach] failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("FAIL {$email}: {$e->getMessage()}");

                continue;
            }

            if ($sent >= $max) {
                break;
            }

            if ($dryRun) {
                continue;
            }

            if ($sent % $batch === 0) {
                $this->warn("{$sent} enviado(s): pausa de lote ({$batchSleep}s)...");
                if ($batchSleep > 0) {
                    sleep($batchSleep);
                }
            } elseif ($minDelay > 0 || $maxDelay > 0) {
                $delay = random_int($minDelay, $maxDelay);
                $this->line("Espera {$delay}s...");
                sleep($delay);
            }
        }

        fclose($fh);

        if ($sent < $max) {
            $this->warn("Se alcanzó el fin del CSV con {$sent} envío(s); objetivo era {$max}.");
        }

        $this->newLine();
        $this->info("Done. sent={$sent}, skipped={$skipped}, failed={$failed}");
        Log::info('[RadarColdOutreach] run_complete', [
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $failed,
            'dry_run' => $dryRun,
        ]);

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        return str_starts_with($path, '/') ? $path : base_path($path);
    }

    /**
     * @param  list<string>  $header
     * @param  list<string>  $candidates
     */
    private function pickColumn(array $header, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            $i = array_search($c, $header, true);
            if ($i !== false) {
                return $header[$i];
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $header
     * @param  list<string|null>  $row
     * @return array<string, string>
     */
    private function rowToAssoc(array $header, array $row): array
    {
        $out = [];
        foreach ($header as $i => $key) {
            $out[$key] = isset($row[$i]) ? trim((string) $row[$i]) : '';
        }

        return $out;
    }
}
