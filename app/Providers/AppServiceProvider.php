<?php

namespace App\Providers;

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
use App\Mail\WelcomeMail;
use App\Support\Blog\ArticleRepository;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

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
