<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrialClaim extends Model
{
    protected $fillable = ['rnc', 'razon_social', 'claimed_by_user_id', 'claimed_at', 'purged_at'];

    protected $casts = [
        'claimed_at' => 'datetime',
        'purged_at' => 'datetime',
    ];

    /** RNCs are written by hand and by the DGCP with inconsistent punctuation. */
    public static function normalize(?string $rnc): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $rnc);

        return $digits === '' ? null : $digits;
    }

    public static function hasClaimed(?string $rnc, ?int $exceptUserId = null): bool
    {
        $normalized = static::normalize($rnc);

        if ($normalized === null) {
            return false;
        }

        return static::query()
            ->where('rnc', $normalized)
            // A claim whose user was deleted has a null owner. `!= $id` is NULL
            // in SQL, not true, so it must be spelled out or the tombstone stops
            // blocking anyone the moment the account behind it goes away.
            ->when($exceptUserId, fn ($q) => $q->where(
                fn ($q2) => $q2->whereNull('claimed_by_user_id')
                    ->orWhere('claimed_by_user_id', '!=', $exceptUserId)
            ))
            ->exists();
    }

    public static function claim(?string $rnc, ?string $razonSocial, ?int $userId): void
    {
        $normalized = static::normalize($rnc);

        if ($normalized === null) {
            return;
        }

        static::firstOrCreate(
            ['rnc' => $normalized],
            ['razon_social' => $razonSocial, 'claimed_by_user_id' => $userId, 'claimed_at' => now()],
        );
    }
}
