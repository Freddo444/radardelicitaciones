<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prellenado_packages', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->index('company_id');
        });

        // Best-effort backfill using the user's current active company at migration time.
        DB::statement('
            UPDATE prellenado_packages p
            JOIN users u ON u.id = p.user_id
            SET p.company_id = u.current_company_id
            WHERE p.company_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('prellenado_packages', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
