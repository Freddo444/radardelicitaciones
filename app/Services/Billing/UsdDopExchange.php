<?php

namespace App\Services\Billing;

use App\Models\Setting;
use Carbon\CarbonImmutable;

final class UsdDopExchange
{
    /** Manually entered by an admin. Used when the mode is manual, and as the
     * fallback before the first successful fetch. */
    public const SETTING_KEY = 'billing_usd_dop_rate';

    /** 'auto' (Banco Popular) or 'manual'. Absent means auto. */
    public const MODE_SETTING_KEY = 'billing_usd_dop_rate_mode';

    /** Last rate read from Banco Popular by billing:refresh-usd-rate. */
    public const AUTO_SETTING_KEY = 'billing_usd_dop_rate_auto';

    public const AUTO_AT_SETTING_KEY = 'billing_usd_dop_rate_auto_at';

    /**
     * DOP per 1 USD for billing (Azul amounts, bank transfer instructions,
     * invoices).
     *
     * The mode decides, and it defaults to auto:
     *
     * - auto   — the bank's rate, falling back to the stored manual value and
     *            then to config until the first successful fetch.
     * - manual — the admin's value, ignoring the bank entirely.
     *
     * A manual value deliberately does NOT outrank the bank on its own. This
     * install has carried one since a migration seeded it, and production had
     * been edited to 59.9, so "manual always wins" would have left automation
     * fetching a rate, reporting success, and changing nothing about what
     * customers were charged. Pausing automation is now something an admin
     * chooses rather than something a leftover row does silently.
     */
    public static function rate(): float
    {
        if (self::isManualMode()) {
            return self::numericSetting(self::SETTING_KEY) ?? self::configFallback();
        }

        return self::autoRate()
            ?? self::numericSetting(self::SETTING_KEY)
            ?? self::configFallback();
    }

    public static function isManualMode(): bool
    {
        return Setting::get(self::MODE_SETTING_KEY, 'auto', null) === 'manual';
    }

    public static function manualRate(): ?float
    {
        return self::numericSetting(self::SETTING_KEY);
    }

    public static function autoRate(): ?float
    {
        return self::numericSetting(self::AUTO_SETTING_KEY);
    }

    public static function autoFetchedAt(): ?CarbonImmutable
    {
        $raw = Setting::get(self::AUTO_AT_SETTING_KEY, null, null);

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    /** 'manual', 'auto' or 'fallback' — which branch rate() actually used. */
    public static function source(): string
    {
        if (self::isManualMode()) {
            return self::numericSetting(self::SETTING_KEY) !== null ? 'manual' : 'fallback';
        }

        if (self::autoRate() !== null) {
            return 'auto';
        }

        return self::numericSetting(self::SETTING_KEY) !== null ? 'manual' : 'fallback';
    }

    /**
     * Whether the automatic rate has gone quiet. True only when there is an
     * automatic rate and it is older than the configured window — an install
     * with no automatic rate at all is not "stale", it is unconfigured.
     */
    public static function autoRateIsStale(): bool
    {
        if (self::autoRate() === null) {
            return false;
        }

        $at = self::autoFetchedAt();
        $maxAgeHours = max(1, (int) config('services.bpd.max_age_hours', 48));

        return $at === null || $at->addHours($maxAgeHours)->isPast();
    }

    public static function configFallback(): float
    {
        return (float) config('services.azul.usd_dop_rate', 62);
    }

    /**
     * The band a rate must fall inside to be believed.
     *
     * This is the guard that matters most. A misread decimal or a shifted
     * point — 6.2 or 620 instead of 62 — would silently bill every customer
     * at a tenth or ten times the price, and the charge path has no other
     * sanity check between the bank's number and the card.
     *
     * @return array{0: float, 1: float}
     */
    public static function plausibleBand(): array
    {
        return [
            (float) config('services.bpd.rate_min', 40),
            (float) config('services.bpd.rate_max', 120),
        ];
    }

    public static function isPlausible(float $rate): bool
    {
        [$min, $max] = self::plausibleBand();

        return $rate >= $min && $rate <= $max;
    }

    private static function numericSetting(string $key): ?float
    {
        $stored = Setting::get($key, null, null);

        if ($stored === null || $stored === '' || ! is_numeric($stored)) {
            return null;
        }

        $value = (float) $stored;

        return $value > 0 ? $value : null;
    }
}
