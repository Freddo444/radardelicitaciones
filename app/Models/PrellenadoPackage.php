<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrellenadoPackage extends Model
{
    protected $fillable = [
        'bid_id', 'user_id', 'form_selections', 'resource_selections',
        'articles_data', 'zip_path', 'zip_sha256',
    ];

    protected $casts = [
        'form_selections' => 'array',
        'resource_selections' => 'array',
        'articles_data' => 'array',
    ];

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(OfferGeneratedFile::class, 'prellenado_package_id');
    }
}
