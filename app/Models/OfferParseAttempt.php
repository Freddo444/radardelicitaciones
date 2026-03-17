<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferParseAttempt extends Model
{
    protected $fillable = [
        'offer_id', 'bid_document_id', 'status', 'confidence_score',
        'parser_version', 'raw_extraction', 'parsed_json', 'failure_reason',
        'human_verified_at', 'human_verified_by', 'triggered_by',
    ];

    protected $casts = [
        'parsed_json' => 'array',
        'human_verified_at' => 'datetime',
        'confidence_score' => 'integer',
    ];

    public static array $statuses = [
        'pending' => ['label' => 'En cola',          'color' => 'bg-gray-100 text-gray-600'],
        'running' => ['label' => 'Analizando...',    'color' => 'bg-blue-50 text-blue-700'],
        'parsed' => ['label' => 'Analizado',        'color' => 'bg-yellow-50 text-yellow-700'],
        'needs_review' => ['label' => 'Revisar',          'color' => 'bg-orange-50 text-orange-700'],
        'verified' => ['label' => 'Verificado',       'color' => 'bg-green-50 text-green-700'],
        'failed' => ['label' => 'Falló',            'color' => 'bg-red-50 text-red-700'],
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function bidDocument(): BelongsTo
    {
        return $this->belongsTo(BidDocument::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'human_verified_by');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'running']);
    }

    public function needsReview(): bool
    {
        return in_array($this->status, ['parsed', 'needs_review']);
    }

    public function statusLabel(): string
    {
        return self::$statuses[$this->status]['label'] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::$statuses[$this->status]['color'] ?? '';
    }
}
