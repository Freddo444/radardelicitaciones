<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Archive expired/closed bids instead of hard-deleting them, so users
        // keep the ability to review opportunities they missed.
        Schema::table('bids', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Track when a vault document's expiry alert was sent, so we notify once.
        Schema::table('vault_documents', function (Blueprint $table) {
            $table->timestamp('expiry_notified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('vault_documents', function (Blueprint $table) {
            $table->dropColumn('expiry_notified_at');
        });
    }
};
