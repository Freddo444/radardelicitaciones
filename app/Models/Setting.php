<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;

    protected $fillable = ['company_id', 'key', 'value', 'updated_at'];

    /**
     * Get a setting value. Pass companyId for per-company settings, null for system-wide.
     */
    public static function get(string $key, mixed $default = null, ?int $companyId = null): mixed
    {
        $row = static::where('key', $key)
            ->where('company_id', $companyId)
            ->first();

        return $row ? $row->value : $default;
    }

    /**
     * Set a setting value. Pass companyId for per-company settings, null for system-wide.
     */
    public static function set(string $key, mixed $value, ?int $companyId = null): void
    {
        static::updateOrCreate(
            ['company_id' => $companyId, 'key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
    }
}
