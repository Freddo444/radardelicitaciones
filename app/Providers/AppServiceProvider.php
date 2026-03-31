<?php

namespace App\Providers;

use App\Models\BidDocument;
use App\Models\BidWatch;
use App\Models\Equipment;
use App\Models\FinancialRecord;
use App\Models\Offer;
use App\Models\OfferParseAttempt;
use App\Models\Personnel;
use App\Models\Project;
use App\Models\Rubro;
use App\Models\VaultDocument;
use App\Policies\CompanyModelPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        date_default_timezone_set('America/Santo_Domingo');

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
