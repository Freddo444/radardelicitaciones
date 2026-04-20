<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['company_id' => null, 'key' => 'billing_usd_dop_rate'],
            ['value' => '62', 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereNull('company_id')
            ->where('key', 'billing_usd_dop_rate')
            ->delete();
    }
};
