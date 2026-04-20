<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'subscription_id', 'amount', 'currency', 'gateway',
        'gateway_payment_id', 'status', 'paid_at', 'invoice_emailed_at', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'invoice_emailed_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function amountFormatted(): string
    {
        $symbol = $this->currency === 'DOP' ? 'RD$' : 'US$';

        return $symbol.number_format((float) $this->amount, 2, '.', ',');
    }

    public function gatewayLabel(): string
    {
        return match ($this->gateway) {
            'paypal' => 'PayPal',
            'azul' => 'Azul',
            'bank_transfer' => 'Transferencia bancaria',
            default => $this->gateway,
        };
    }
}
