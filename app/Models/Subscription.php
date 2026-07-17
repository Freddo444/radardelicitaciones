<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'plan', 'status',
        'current_period_start', 'current_period_end',
        'max_companies', 'max_users', 'monthly_amount',
        'payment_gateway', 'gateway_subscription_id', 'gateway_customer_id',
        'cancelled_at', 'trial_ends_at', 'trial_parse_count', 'trial_parse_limit',
        'billing_cycle',
    ];

    protected $casts = [
        'current_period_start' => 'date',
        'current_period_end' => 'date',
        'cancelled_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'monthly_amount' => 'decimal:2',
        'max_companies' => 'integer',
        'max_users' => 'integer',
        'trial_parse_count' => 'integer',
        'trial_parse_limit' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' || $this->isTrialing();
    }

    public function isTrialing(): bool
    {
        if ($this->status !== 'trialing') {
            return false;
        }

        // Legacy safety: if trial_ends_at is missing, keep access enabled.
        if (! $this->trial_ends_at) {
            return true;
        }

        // Treat date-only values as valid through the end of that day.
        return $this->trial_ends_at->copy()->endOfDay()->isFuture();
    }

    public function trialExpired(): bool
    {
        return $this->status === 'trialing'
            && $this->trial_ends_at
            && $this->trial_ends_at->copy()->endOfDay()->isPast();
    }

    public function canUseTrial(): bool
    {
        return $this->isTrialing() && $this->trial_parse_count < $this->trial_parse_limit;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /** A bank-transfer voucher has been submitted and is awaiting admin confirmation. */
    public function hasPendingBankTransfer(): bool
    {
        return $this->payments()
            ->where('gateway', 'bank_transfer')
            ->where('status', 'pending')
            ->exists();
    }

    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }

    public function companyCount(): int
    {
        return Company::whereIn('id', function ($q) {
            $q->select('company_id')
                ->from('company_user')
                ->where('user_id', $this->user_id);
        })->count();
    }

    public function userCount(): int
    {
        return User::whereIn('id', function ($q) {
            $q->select('user_id')
                ->from('company_user')
                ->whereIn('company_id', function ($q2) {
                    $q2->select('company_id')
                        ->from('company_user')
                        ->where('user_id', $this->user_id);
                });
        })->distinct()->count();
    }
}
