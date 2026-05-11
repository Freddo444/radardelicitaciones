<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleCalendarEventMap extends Model
{
    protected $table = 'google_calendar_event_map';

    public const TYPE_OFFER_DEADLINE = 'offer_deadline';

    public const TYPE_OFFER_EVENT = 'offer_event';

    protected $fillable = [
        'google_calendar_token_id',
        'syncable_type', 'syncable_id',
        'google_event_id', 'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
        'syncable_id' => 'integer',
    ];

    public function token(): BelongsTo
    {
        return $this->belongsTo(GoogleCalendarToken::class, 'google_calendar_token_id');
    }
}
