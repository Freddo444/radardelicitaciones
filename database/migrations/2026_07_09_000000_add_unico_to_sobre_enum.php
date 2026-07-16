<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'U' (Sobre Único) to the existing enum('A','B'). Requires a raw
        // MODIFY on MySQL; no-op on SQLite (dev) where enums are just text.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE offer_requirements MODIFY COLUMN sobre ENUM('A', 'B', 'U') NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Clear any 'U' assignments before shrinking the enum back, or the
        // MODIFY would truncate them to an empty string.
        DB::table('offer_requirements')->where('sobre', 'U')->update(['sobre' => null]);
        DB::statement("ALTER TABLE offer_requirements MODIFY COLUMN sobre ENUM('A', 'B') NULL");
    }
};
