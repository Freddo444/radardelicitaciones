<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidDocument extends Model
{
    protected $fillable = [
        'offer_id', 'document_type', 'original_filename',
        'source_url', 'downloaded_at', 'sha256', 'local_path', 'file_size_bytes',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function fileSizeFormatted(): string
    {
        if (! $this->file_size_bytes) {
            return '—';
        }
        if ($this->file_size_bytes >= 1048576) {
            return round($this->file_size_bytes / 1048576, 1).' MB';
        }

        return round($this->file_size_bytes / 1024, 1).' KB';
    }
}
