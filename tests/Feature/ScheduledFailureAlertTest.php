<?php

namespace Tests\Feature;

use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Exceptions;
use RuntimeException;
use Tests\TestCase;

/**
 * Laravel reports a scheduled command's non-zero exit only when the command
 * runs in the foreground: ScheduleRunCommand guards its throw with
 * `! $event->runInBackground`, and schedule:finish just records the code.
 * Every ingestion command in routes/console.php uses runInBackground(), so
 * secp:scrape failed every fifteen minutes from 2026-11-02 to 2026-11-05 —
 * roughly 330 runs, each logging an error — and raised nothing in Sentry.
 */
class ScheduledFailureAlertTest extends TestCase
{
    private function finish(string $command, int $exitCode): void
    {
        $task = app(Schedule::class)->exec($command);
        $task->exitCode = $exitCode;

        event(new ScheduledBackgroundTaskFinished($task));
    }

    public function test_a_failed_background_command_is_reported(): void
    {
        Exceptions::fake();

        $this->finish('php -d memory_limit=2G /var/www/html/radar/artisan secp:scrape', 1);

        Exceptions::assertReported(fn (RuntimeException $e) => $e->getMessage()
            === 'Scheduled command [secp:scrape] failed with exit code [1].');
    }

    public function test_a_successful_background_command_is_not_reported(): void
    {
        Exceptions::fake();

        $this->finish('php -d memory_limit=2G /var/www/html/radar/artisan secp:scrape', 0);

        Exceptions::assertNothingReported();
    }

    public function test_the_report_names_the_artisan_command_not_the_whole_shell_line(): void
    {
        Exceptions::fake();

        // The real scheduled line carries the memory limit, an output path and
        // the schedule:finish tail. Grouping on that would open a fresh Sentry
        // issue whenever any of it changed.
        $this->finish(
            "php -d memory_limit=2G '/var/www/html/radar/artisan' secp:import-catalog "
            .">> '/var/www/html/radar/storage/logs/secp-import-catalog.log' 2>&1",
            1
        );

        Exceptions::assertReported(fn (RuntimeException $e) => $e->getMessage()
            === 'Scheduled command [secp:import-catalog] failed with exit code [1].');
    }
}
