<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferProject extends Model
{
    protected $fillable = ['offer_id', 'project_id', 'role_note'];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
