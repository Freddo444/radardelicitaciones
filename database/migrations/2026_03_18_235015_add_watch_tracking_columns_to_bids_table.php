<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            $table->string('last_known_status')->nullable()->after('cache_refreshed_at');
            $table->unsignedInteger('last_known_doc_count')->nullable()->after('last_known_status');
        });
    }

    public function down(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            $table->dropColumn(['last_known_status', 'last_known_doc_count']);
        });
    }
};
