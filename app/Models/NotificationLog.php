<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use BelongsToCompany;

    public $timestamps = false;

    protected $table = 'notification_log';

    protected $fillable = ['bid_id', 'company_id', 'channel', 'status', 'error_message', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function bid()
    {
        return $this->belongsTo(Bid::class);
    }
}
