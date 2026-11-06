<?php

namespace App\Providers;

use App\Mail\WelcomeMail;
use App\Models\BidDocument;
use App\Models\BidWatch;
use App\Models\Equipment;
use App\Models\FinancialRecord;
use App\Models\Offer;
use App\Models\OfferEvent;
use App\Models\OfferParseAttempt;
use App\Models\Payment;
use App\Models\Personnel;
use App\Models\Project;
use App\Models\Rubro;
use App\Models\User;
use App\Models\VaultDocument;
use App\Observers\OfferEventObserver;
use App\Observers\OfferObserver;
use App\Observers\PaymentObserver;
use App\Policies\CompanyModelPolicy;
use App\Support\Blog\ArticleRepository;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Verified;
use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ArticleRepository::class, function () {
            return new ArticleRepository(resource_path('articles'));
        });
    }

    public function boot(): void
    {
        date_default_timezone_set('America/Santo_Domingo');
        Carbon::setLocale('es');

        // A scheduled command marked runInBackground() that exits non-zero is
        // reported nowhere. ScheduleRunCommand throws only for foreground
        // events — `$event->exitCode != 0 && ! $event->runInBackground` — and
        // schedule:finish merely records the code. Every ingestion command
        // runs in the background, which is why secp:scrape could fail every
        // fifteen minutes from 2026-11-02 to 2026-11-05, log an error each
        // time, and never raise a single Sentry issue. (A command that throws
        // is still reported; one that returns a failure exit code was not.)
        Event::listen(ScheduledBackgroundTaskFinished::class, function (ScheduledBackgroundTaskFinished $event): void {
            $exitCode = (int) $event->task->exitCode;

            if ($exitCode === 0) {
                return;
            }

            $command = (string) $event->task->command;
            // Group by the artisan command, not the whole shell line: the full
            // line carries the memory limit, the output path and the mutex.
            $name = preg_match('/artisan[\'"]?\s+(\S+)/', $command, $m) ? $m[1] : $command;

            report(new RuntimeException(
                "Scheduled command [{$name}] failed with exit code [{$exitCode}]."
            ));
        });

        Event::listen(Login::class, function (Login $event): void {
            $user = $event->user;
            if ($user instanceof User) {
                $user->forceFill(['last_sign_in_at' => now()])->saveQuietly();
            }
        });

        // Send a one-time welcome email once the user verifies their address.
        Event::listen(Verified::class, function (Verified $event): void {
            $user = $event->user;
            if (! $user instanceof User || $user->welcome_sent_at) {
                return;
            }

            try {
                Mail::to($user->email)->queue(new WelcomeMail($user));
                $user->forceFill(['welcome_sent_at' => now()])->saveQuietly();
            } catch (\Throwable $e) {
                Log::error('[Welcome] send failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        });

        Payment::observe(PaymentObserver::class);
        Offer::observe(OfferObserver::class);
        OfferEvent::observe(OfferEventObserver::class);

        $tenantModels = [
            Offer::class, Personnel::class, Equipment::class,
            Project::class, FinancialRecord::class, VaultDocument::class,
            Rubro::class, OfferParseAttempt::class, BidDocument::class,
            BidWatch::class,
        ];

        foreach ($tenantModels as $model) {
            Gate::policy($model, CompanyModelPolicy::class);
        }
    }
}
