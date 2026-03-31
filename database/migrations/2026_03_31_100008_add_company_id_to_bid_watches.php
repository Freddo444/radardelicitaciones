<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bid_watches', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')
                ->constrained()->cascadeOnDelete();

            // Must drop FK on bid_id before dropping the unique index it depends on
            $table->dropForeign(['bid_id']);
            $table->dropUnique(['bid_id', 'user_id']);
            $table->foreign('bid_id')->references('id')->on('bids')->cascadeOnDelete();
            $table->unique(['company_id', 'bid_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('bid_watches', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'bid_id', 'user_id']);
            $table->dropForeign(['bid_id']);
            $table->dropConstrainedForeignId('company_id');
            $table->unique(['bid_id', 'user_id']);
            $table->foreign('bid_id')->references('id')->on('bids')->cascadeOnDelete();
        });
    }
};
