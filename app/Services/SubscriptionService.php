<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;

class SubscriptionService
{
    public const BASE_PRICE = 45.00;

    public const EXTRA_COMPANY_PRICE = 20.00;

    public const EXTRA_USER_PRICE = 10.00;

    public const BASE_COMPANIES = 1;

    public const BASE_USERS = 2;

    public const ANNUAL_DISCOUNT = 0.20;

    public const TRIAL_DAYS = 7;

    public const TRIAL_PARSE_LIMIT = 2;

    /**
     * Calculate monthly amount based on subscription limits.
     */
    public static function calculateMonthly(int $maxCompanies = 1, int $maxUsers = 2): float
    {
        $extraCompanies = max(0, $maxCompanies - self::BASE_COMPANIES);
        $extraUsers = max(0, $maxUsers - self::BASE_USERS);

        return self::BASE_PRICE
            + ($extraCompanies * self::EXTRA_COMPANY_PRICE)
            + ($extraUsers * self::EXTRA_USER_PRICE);
    }

    public static function calculateAnnual(int $maxCompanies = 1, int $maxUsers = 2): float
    {
        return round(static::calculateMonthly($maxCompanies, $maxUsers) * 12 * (1 - self::ANNUAL_DISCOUNT), 2);
    }

    public static function calculatePrice(int $maxCompanies, int $maxUsers, string $billingCycle = 'monthly'): float
    {
        return $billingCycle === 'annual'
            ? static::calculateAnnual($maxCompanies, $maxUsers)
            : static::calculateMonthly($maxCompanies, $maxUsers);
    }

    /**
     * Check if the subscription owner can add another company.
     */
    public static function canAddCompany(Subscription $subscription): bool
    {
        return $subscription->companyCount() < $subscription->max_companies;
    }

    /**
     * Check if the subscription owner can add another user (across all companies).
     */
    public static function canAddUser(Subscription $subscription): bool
    {
        return $subscription->userCount() < $subscription->max_users;
    }

    /**
     * Activate a subscription after first payment is confirmed.
     */
    public static function activate(Subscription $subscription): void
    {
        $subscription->update([
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
    }

    /**
     * Record a payment and activate subscription if pending.
     */
    public static function recordPayment(
        Subscription $subscription,
        float $amount,
        string $currency,
        string $gateway,
        ?string $gatewayPaymentId = null,
        ?string $notes = null,
    ): Payment {
        $payment = Payment::create([
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'currency' => $currency,
            'gateway' => $gateway,
            'gateway_payment_id' => $gatewayPaymentId,
            'status' => 'completed',
            'paid_at' => now(),
            'notes' => $notes,
        ]);

        $period = $subscription->billing_cycle === 'annual' ? now()->addYear() : now()->addMonth();

        // Activate if first payment on a pending or trialing subscription
        if ($subscription->isPending() || $subscription->trialExpired() || $subscription->status === 'trialing') {
            $subscription->update([
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => $period,
            ]);
        } elseif ($subscription->status === 'active') {
            $subscription->update([
                'current_period_end' => $period,
            ]);
        }

        return $payment;
    }

    /**
     * Calculate prorated one-time charge for an add-on mid-cycle.
     */
    public static function calculateProration(Subscription $subscription, float $addonMonthlyPrice): float
    {
        if ($subscription->billing_cycle === 'annual') {
            $periodStart = $subscription->current_period_start;
            $periodEnd = $subscription->current_period_end;
            $totalDays = $periodStart->diffInDays($periodEnd);
            $remainingDays = max(0, now()->startOfDay()->diffInDays($periodEnd, false));
            $annualAddon = $addonMonthlyPrice * 12 * (1 - self::ANNUAL_DISCOUNT);

            return $totalDays > 0 ? round($annualAddon * ($remainingDays / $totalDays), 2) : 0;
        }

        // Monthly: prorate based on days remaining in current period
        $periodStart = $subscription->current_period_start;
        $periodEnd = $subscription->current_period_end;
        $totalDays = $periodStart->diffInDays($periodEnd);
        $remainingDays = max(0, now()->startOfDay()->diffInDays($periodEnd, false));

        return $totalDays > 0 ? round($addonMonthlyPrice * ($remainingDays / $totalDays), 2) : 0;
    }

    /**
     * New monthly amount after adding users/companies.
     */
    public static function newMonthlyAmount(int $maxCompanies, int $maxUsers): float
    {
        return self::calculateMonthly($maxCompanies, $maxUsers);
    }

    /**
     * New recurring amount (monthly or annual) after adding users/companies.
     */
    public static function newRecurringAmount(int $maxCompanies, int $maxUsers, string $billingCycle = 'monthly'): float
    {
        return self::calculatePrice($maxCompanies, $maxUsers, $billingCycle);
    }

    /**
     * Usage summary for the billing page.
     */
    public static function usage(Subscription $subscription): array
    {
        $usage = [
            'companies' => $subscription->companyCount(),
            'max_companies' => $subscription->max_companies,
            'users' => $subscription->userCount(),
            'max_users' => $subscription->max_users,
            'monthly_amount' => $subscription->monthly_amount,
        ];

        if ($subscription->status === 'trialing') {
            $usage['trial_parses_used'] = $subscription->trial_parse_count;
            $usage['trial_parses_limit'] = $subscription->trial_parse_limit;
            $usage['trial_days_left'] = $subscription->trial_ends_at
                ? max(0, (int) now()->diffInDays($subscription->trial_ends_at, false))
                : 0;
        }

        return $usage;
    }
}
