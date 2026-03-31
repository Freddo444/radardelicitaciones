<?php

use App\Models\Company;

if (! function_exists('currentCompany')) {
    function currentCompany(): ?Company
    {
        if (app()->bound('currentCompany')) {
            return app('currentCompany');
        }

        return null;
    }
}
