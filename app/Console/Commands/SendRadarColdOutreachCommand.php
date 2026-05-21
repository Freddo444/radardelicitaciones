<?php

namespace App\Console\Commands;

use App\Mail\RadarColdOutreachMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Console\Helper\ProgressBar;

class SendRadarColdOutreachCommand extends Command
{
    protected $signature = 'secp:send-radar-cold-outreach
        {--csv=storage/app/proveedores-outreach-150.csv : Path to CSV (relative to project root or absolute)}
        {--tracking-url= : Target URL for the CTA (trial/register)}
        {--max=150 : Stop after this many successful sends this invocation}
        {--daily-cap=0 : Stop when N total sends have happened today across all invocations (0 = no cap)}
        {--batch=20 : After this many successful sends, sleep --batch-sleep seconds}
        {--batch-sleep=1800 : Pause after each batch (default 1800 = 30 minutes)}
        {--min-delay=60 : Minimum seconds between successful sends (skipped rows do not wait)}
        {--max-delay=180 : Maximum seconds between successful sends}
        {--sent-log=storage/app/outreach-sent.log : Path to dedupe log (one ISO_ts<tab>email per line)}
        {--dry-run : Log recipients without sending}';

    protected $description = 'Send RadarColdOutreachMail from CSV with throttling, sent-log dedupe, and daily cap';

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
        $dailyCap = max(0, (int) $this->option('daily-cap'));
        $batch = max(1, (int) $this->option('batch'));
        $batchSleep = max(0, (int) $this->option('batch-sleep'));
        $minDelay = max(0, (int) $this->option('min-delay'));
        $maxDelay = max($minDelay, (int) $this->option('max-delay'));
        $dryRun = (bool) $this->option('dry-run');

        $sentLogPath = $this->resolvePath((string) $this->option('sent-log'));
        [$sentEmails, $sentTodayCount] = $this->loadSentLog($sentLogPath);

        if ($dailyCap > 0 && $sentTodayCount >= $dailyCap) {
            $this->warn("Daily cap of {$dailyCap} already reached today ({$sentTodayCount} sent). Nothing to do.");

            return self::SUCCESS;
        }

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

        // Strip UTF-8 BOM from first column header (Excel-saved CSVs include it)
        $header = array_map(
            fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))),
            $header
        );
        $emailKey = $this->pickColumn($header, ['email', 'correo']);
        $nameKey = $this->pickColumn($header, ['company_name', 'razon_social', 'nombre', 'empresa', 'company_guess']);
        $firstNameKey = $this->pickColumn($header, ['first_name', 'nombre_pila', 'primer_nombre']);
        if ($emailKey === null) {
            fclose($fh);
            $this->error('CSV must include an email column (email or correo).');

            return self::FAILURE;
        }

        $skipped = 0;
        $failed = 0;
        $sent = 0;
        $startedAt = microtime(true);

        $this->info('CSV: '.$csvPath.($dryRun ? ' (dry-run)' : ''));
        $this->info('Sent-log: '.$sentLogPath.' ('.count($sentEmails).' previously-sent, '.$sentTodayCount.' today)');
        $this->info("Target: {$max} this run".($dailyCap > 0 ? " (daily cap {$dailyCap})" : '').
            "; batch {$batch} then {$batchSleep}s pause; delay {$minDelay}-{$maxDelay}s.");

        $bar = $this->createOutreachProgressBar($max);
        $bar->start();

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
            $firstName = $firstNameKey !== null ? trim((string) ($data[$firstNameKey] ?? '')) : '';
            $firstName = $firstName !== '' ? $firstName : null;

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                Log::info('[RadarColdOutreach] skipped_invalid_email', ['email' => $email]);
                $bar->clear();
                $this->line("SKIP invalid email: {$email}");
                $bar->display();

                continue;
            }

            if (isset($sentEmails[$email])) {
                $skipped++;

                continue;
            }

            if ($dailyCap > 0 && $sentTodayCount >= $dailyCap) {
                $bar->clear();
                $this->warn("Daily cap of {$dailyCap} reached. Stopping.");
                break;
            }

            try {
                if ($dryRun) {
                    $bar->clear();
                    $detail = $firstName ? "{$firstName} @ {$companyName}" : $companyName;
                    $this->line("[DRY] → {$email} ({$detail})");
                    $bar->display();
                } else {
                    Mail::to($email)->send(new RadarColdOutreachMail($companyName, $trackingUrl, $firstName));
                    $this->appendSentLog($sentLogPath, $email);
                    $sentEmails[$email] = true;
                    $sentTodayCount++;
                }
                $sent++;
                Log::info('[RadarColdOutreach] sent', [
                    'email' => $email,
                    'company' => $companyName,
                    'count' => $sent,
                ]);
                $bar->setMessage($this->progressMessage($sent, $max, $startedAt, $email));
                $bar->advance();
            } catch (\Throwable $e) {
                $failed++;
                Log::error('[RadarColdOutreach] failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
                $bar->clear();
                $this->warn("FAIL {$email}: {$e->getMessage()}");
                $bar->display();

                continue;
            }

            if ($sent >= $max) {
                break;
            }

            if ($dryRun) {
                continue;
            }

            if ($sent % $batch === 0) {
                $bar->setMessage("pausa de lote {$batchSleep}s tras {$sent} envíos");
                $bar->display();
                $bar->clear();
                $this->warn("{$sent} enviado(s): pausa de lote ({$batchSleep}s)...");
                if ($batchSleep > 0) {
                    sleep($batchSleep);
                }
                $bar->setMessage($this->progressMessage($sent, $max, $startedAt, 'reanudando'));
                $bar->display();
            } elseif ($minDelay > 0 || $maxDelay > 0) {
                $delay = random_int($minDelay, $maxDelay);
                $bar->setMessage("espera {$delay}s — ETA ".$this->formatEta($sent, $max, $startedAt));
                $bar->display();
                sleep($delay);
            }
        }

        $bar->finish();
        fclose($fh);

        if ($sent < $max) {
            $this->warn("Se alcanzó el fin del CSV con {$sent} envío(s); objetivo era {$max}.");
        }

        $this->newLine();
        $this->info("Done. sent={$sent}, skipped={$skipped}, failed={$failed}, elapsed=".$this->formatDuration(microtime(true) - $startedAt));
        Log::info('[RadarColdOutreach] run_complete', [
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $failed,
            'dry_run' => $dryRun,
        ]);

        return self::SUCCESS;
    }

    private function createOutreachProgressBar(int $max): ProgressBar
    {
        $bar = $this->output->createProgressBar($max);
        $bar->setFormat(
            ' %current%/%max% [%bar%] %percent:3s%% — %elapsed:6s% / ~%estimated:-6s% — %message%'
        );

        return $bar;
    }

    private function progressMessage(int $sent, int $max, float $startedAt, string $detail): string
    {
        $eta = $this->formatEta($sent, $max, $startedAt);

        return "último: {$detail}".($eta !== null ? " — restante ~{$eta}" : '');
    }

    private function formatEta(int $sent, int $max, float $startedAt): ?string
    {
        if ($sent <= 0 || $sent >= $max) {
            return null;
        }

        $elapsed = microtime(true) - $startedAt;
        $remaining = ($elapsed / $sent) * ($max - $sent);

        return $this->formatDuration($remaining);
    }

    private function formatDuration(float $seconds): string
    {
        $seconds = max(0, (int) round($seconds));
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        if ($h > 0) {
            return sprintf('%dh %02dm', $h, $m);
        }

        if ($m > 0) {
            return sprintf('%dm %02ds', $m, $s);
        }

        return "{$s}s";
    }

    private function resolvePath(string $path): string
    {
        return str_starts_with($path, '/') ? $path : base_path($path);
    }

    /**
     * @return array{0: array<string, true>, 1: int} [sentEmails map, count_today]
     */
    private function loadSentLog(string $path): array
    {
        $sentEmails = [];
        $today = now()->toDateString();
        $sentTodayCount = 0;

        if (! file_exists($path)) {
            return [$sentEmails, $sentTodayCount];
        }

        $fh = fopen($path, 'r');
        if ($fh === false) {
            return [$sentEmails, $sentTodayCount];
        }

        while (($line = fgets($fh)) !== false) {
            $parts = explode("\t", trim($line), 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$ts, $email] = $parts;
            $email = strtolower(trim($email));
            if ($email === '') {
                continue;
            }
            $sentEmails[$email] = true;
            if (str_starts_with($ts, $today)) {
                $sentTodayCount++;
            }
        }
        fclose($fh);

        return [$sentEmails, $sentTodayCount];
    }

    private function appendSentLog(string $path, string $email): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $line = now()->toIso8601String()."\t".$email."\n";
        file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
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
