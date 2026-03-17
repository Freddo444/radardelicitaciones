<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferPersonnel extends Model
{
    protected $fillable = ['offer_id', 'personnel_id', 'role_note'];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'personnel_id');
    }
}
