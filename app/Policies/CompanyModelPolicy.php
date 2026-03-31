<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CompanyModelPolicy
{
    public function view(User $user, Model $model): bool
    {
        return $this->belongsToSameCompany($user, $model);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->belongsToSameCompany($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->belongsToSameCompany($user, $model);
    }

    private function belongsToSameCompany(User $user, Model $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $companyId = $model->company_id ?? null;

        if (! $companyId) {
            return false;
        }

        return $user->belongsToCompany($companyId);
    }
}
