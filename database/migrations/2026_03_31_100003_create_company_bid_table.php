<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_bid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bid_id')->constrained()->cascadeOnDelete();
            $table->json('matched_rubros')->nullable();
            $table->boolean('is_bookmarked')->default(false);
            $table->boolean('is_relevant')->default(false);
            $table->timestamp('first_matched_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'bid_id']);
            $table->index(['company_id', 'is_bookmarked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_bid');
    }
};
