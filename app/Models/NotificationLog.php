<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    public $timestamps = false;

    protected $table = 'notification_log';

    protected $fillable = ['bid_id', 'channel', 'status', 'error_message', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function bid()
    {
        return $this->belongsTo(Bid::class);
    }
}
