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

        // Activate if this is the first payment on a pending subscription
        if ($subscription->isPending()) {
            static::activate($subscription);
        }

        // Extend period if already active
        if ($subscription->isActive()) {
            $subscription->update([
                'current_period_end' => now()->addMonth(),
            ]);
        }

        return $payment;
    }

    /**
     * Usage summary for the billing page.
     */
    public static function usage(Subscription $subscription): array
    {
        return [
            'companies' => $subscription->companyCount(),
            'max_companies' => $subscription->max_companies,
            'users' => $subscription->userCount(),
            'max_users' => $subscription->max_users,
            'monthly_amount' => $subscription->monthly_amount,
        ];
    }
}
