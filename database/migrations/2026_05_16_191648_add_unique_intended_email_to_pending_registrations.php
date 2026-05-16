<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// MySQL has no partial/filtered unique indexes. We simulate one with a stored
// generated column that holds intended_email only while the row is unclaimed
// and unrefunded, and NULL at all other times. A unique index on that column
// prevents two active rows for the same email at the DB level.
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE pending_registrations
            ADD COLUMN active_email VARCHAR(255)
                AS (IF(claimed_at IS NULL AND refunded_at IS NULL, intended_email, NULL))
                STORED,
            ADD UNIQUE INDEX unique_active_pending_email (active_email)
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE pending_registrations DROP INDEX unique_active_pending_email');
        DB::statement('ALTER TABLE pending_registrations DROP COLUMN active_email');
    }
};
