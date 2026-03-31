<?php

namespace App\Models\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        // Auto-scope queries to current company when in web context
        static::addGlobalScope('company', function (Builder $query) {
            $company = currentCompany();
            if ($company) {
                $query->where($query->getModel()->getTable().'.company_id', $company->id);
            }
        });

        // Auto-set company_id on creation if not already set
        static::creating(function ($model) {
            if (! $model->company_id) {
                $company = currentCompany();
                if ($company) {
                    $model->company_id = $company->id;
                }
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
