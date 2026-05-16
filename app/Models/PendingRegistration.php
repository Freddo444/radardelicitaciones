<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingRegistration extends Model
{
    protected $fillable = [
        'order_number', 'rrn', 'auth_code', 'iso_code', 'card_last_four',
        'plan', 'intended_email', 'claimed_at', 'claimed_by_user_id',
        'refunded_at', 'expires_at',
    ];

    protected $casts = [
        'plan' => 'array',
        'claimed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }

    public function isOrphan(): bool
    {
        return $this->claimed_at === null && $this->created_at->lt(now()->subHours(24));
    }
}
