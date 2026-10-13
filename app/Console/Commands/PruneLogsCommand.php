<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Keeps storage/logs from growing without limit.
 *
 * Laravel's daily driver only rotates what Laravel itself writes. The
 * scheduler's appendOutputTo files — secp-poll.log and friends — are plain
 * shell appends nothing prunes, and they had reached 101MB. Size mattered
 * beyond disk: grepping a 100MB log is slow enough that a three-day scraper
 * outage went unnoticed inside it.
 *
 * Oversized files are truncated in place rather than renamed, so a process
 * holding the handle open (the queue worker under supervisor) keeps writing to
 * the same inode instead of into an unlinked file. The tail is preserved so
 * recent context survives.
 */
class PruneLogsCommand extends Command
{
    protected $signature = 'logs:prune
        {--max-mb=20 : Truncate any log larger than this}
        {--keep-kb=512 : How much of the tail to keep when truncating}
        {--days=14 : Delete dated laravel-*.log files older than this}
        {--dry-run : Report what would change without touching anything}';

    protected $description = 'Truncate oversized logs and delete old dated ones';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $maxBytes = max(1, (int) $this->option('max-mb')) * 1024 * 1024;
        $keepBytes = max(1, (int) $this->option('keep-kb')) * 1024;
        $days = max(1, (int) $this->option('days'));

        $dir = storage_path('logs');
        $truncated = 0;
        $deleted = 0;
        $freed = 0;

        foreach (glob($dir.'/*.log') ?: [] as $path) {
            $name = basename($path);
            $size = (int) @filesize($path);

            // Dated files are complete; once they are old enough they go whole.
            if (preg_match('/^laravel-\d{4}-\d{2}-\d{2}\.log$/', $name)
                && @filemtime($path) < now()->subDays($days)->getTimestamp()) {
                $this->line(($dryRun ? '[DRY] ' : '').'delete '.$name.' ('.$this->mb($size).')');
                if (! $dryRun && @unlink($path)) {
                    $deleted++;
                    $freed += $size;
                }

                continue;
            }

            if ($size <= $maxBytes) {
                continue;
            }

            $this->line(($dryRun ? '[DRY] ' : '').'truncate '.$name.' ('.$this->mb($size).' → '.$this->mb($keepBytes).')');

            if ($dryRun) {
                continue;
            }

            if ($this->truncateKeepingTail($path, $keepBytes)) {
                $truncated++;
                $freed += $size - (int) @filesize($path);
            }
        }

        $summary = sprintf(
            '%s %d truncado(s), %d eliminado(s), %s liberado(s).',
            $dryRun ? 'Dry run:' : 'Logs:',
            $truncated,
            $deleted,
            $this->mb($freed)
        );

        $this->info($summary);

        if (! $dryRun && ($truncated || $deleted)) {
            Log::info('[PruneLogs] '.$summary);
        }

        return self::SUCCESS;
    }

    /**
     * Replace a file's contents with its own tail, keeping the inode. Trimmed
     * forward to the next newline so the file does not start mid-record.
     */
    private function truncateKeepingTail(string $path, int $keepBytes): bool
    {
        $handle = @fopen($path, 'r+');

        if (! $handle) {
            $this->warn('  no se pudo abrir '.basename($path));

            return false;
        }

        try {
            if (! flock($handle, LOCK_EX | LOCK_NB)) {
                $this->warn('  '.basename($path).' está en uso, se omite');

                return false;
            }

            $size = (int) fstat($handle)['size'];
            fseek($handle, max(0, $size - $keepBytes));
            $tail = (string) fread($handle, $keepBytes);

            $newline = strpos($tail, "\n");
            if ($newline !== false && $newline < strlen($tail) - 1) {
                $tail = substr($tail, $newline + 1);
            }

            $tail = '[truncated by logs:prune at '.now()->toDateTimeString()."]\n".$tail;

            rewind($handle);
            fwrite($handle, $tail);
            ftruncate($handle, strlen($tail));
            fflush($handle);
            flock($handle, LOCK_UN);

            return true;
        } finally {
            fclose($handle);
        }
    }

    private function mb(int $bytes): string
    {
        return number_format($bytes / 1024 / 1024, 1).'MB';
    }
}
