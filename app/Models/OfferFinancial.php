<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferFinancial extends Model
{
    protected $fillable = ['offer_id', 'financial_record_id', 'role_note'];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function financialRecord(): BelongsTo
    {
        return $this->belongsTo(FinancialRecord::class);
    }
}
