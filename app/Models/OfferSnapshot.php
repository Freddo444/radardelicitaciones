<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferSnapshot extends Model
{
    protected $fillable = [
        'offer_id',
        'company_snapshot', 'personnel_snapshot', 'projects_snapshot',
        'equipment_snapshot', 'financials_snapshot', 'requirements_snapshot',
        'file_hashes', 'zip_path', 'zip_sha256', 'assembled_at', 'assembled_by',
    ];

    protected $casts = [
        'company_snapshot' => 'array',
        'personnel_snapshot' => 'array',
        'projects_snapshot' => 'array',
        'equipment_snapshot' => 'array',
        'financials_snapshot' => 'array',
        'requirements_snapshot' => 'array',
        'file_hashes' => 'array',
        'assembled_at' => 'datetime',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function assembledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assembled_by');
    }

    public function zipSizeFormatted(): string
    {
        if (! $this->zip_path || ! file_exists(storage_path('app/'.$this->zip_path))) {
            return '—';
        }
        $bytes = filesize(storage_path('app/'.$this->zip_path));
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        return round($bytes / 1024, 1).' KB';
    }
}
