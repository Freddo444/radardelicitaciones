<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bid extends Model
{
    protected $fillable = [
        'process_code', 'ocid', 'title', 'buyer_name', 'buyer_code',
        'procurement_method', 'status', 'amount_estimated', 'currency',
        'published_at', 'tender_deadline', 'matched_rubros', 'secp_url',
        'raw_data', 'notified_at',
    ];

    protected $casts = [
        'matched_rubros' => 'array',
        'raw_data' => 'array',
        'published_at' => 'datetime',
        'tender_deadline' => 'datetime',
        'notified_at' => 'datetime',
        'amount_estimated' => 'decimal:2',
    ];

    public function notificationLogs()
    {
        return $this->hasMany(NotificationLog::class);
    }
}
