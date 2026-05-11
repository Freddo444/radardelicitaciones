<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_calendar_event_map', function (Blueprint $table) {
            $table->id();
            $table->foreignId('google_calendar_token_id')->constrained('google_calendar_tokens')->cascadeOnDelete();
            $table->string('syncable_type', 32);
            $table->unsignedBigInteger('syncable_id');
            $table->string('google_event_id');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['google_calendar_token_id', 'syncable_type', 'syncable_id'], 'gcem_token_target_unique');
            $table->index(['syncable_type', 'syncable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_calendar_event_map');
    }
};
