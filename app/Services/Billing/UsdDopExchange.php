<?php

namespace App\Services\Billing;

use App\Models\Setting;

final class UsdDopExchange
{
    public const SETTING_KEY = 'billing_usd_dop_rate';

    /**
     * DOP per 1 USD for billing (Azul amounts). Admin-stored value overrides config/env.
     */
    public static function rate(): float
    {
        $stored = Setting::get(self::SETTING_KEY, null, null);
        if ($stored !== null && $stored !== '' && is_numeric($stored)) {
            $v = (float) $stored;

            return $v > 0 ? $v : self::configFallback();
        }

        return self::configFallback();
    }

    public static function configFallback(): float
    {
        return (float) config('services.azul.usd_dop_rate', 62);
    }
}
