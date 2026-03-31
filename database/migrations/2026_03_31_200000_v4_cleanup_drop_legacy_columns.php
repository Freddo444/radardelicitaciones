<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop legacy bid-level columns now replaced by company_bid pivot
        Schema::table('bids', function (Blueprint $table) {
            $table->dropColumn(['matched_rubros', 'is_bookmarked', 'is_relevant', 'notified_at']);
        });

        // Drop legacy role column now replaced by is_super_admin
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            $table->json('matched_rubros')->nullable();
            $table->boolean('is_bookmarked')->default(false);
            $table->boolean('is_relevant')->default(false);
            $table->timestamp('notified_at')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('viewer');
        });
    }
};
