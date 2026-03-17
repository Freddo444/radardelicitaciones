<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferEquipment extends Model
{
    protected $fillable = ['offer_id', 'equipment_id', 'role_note'];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
