<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->string('process_code')->unique();
            $table->string('ocid')->nullable();
            $table->string('title');
            $table->string('buyer_name')->nullable();
            $table->string('buyer_code')->nullable();
            $table->string('procurement_method')->nullable();
            $table->string('status')->nullable();
            $table->decimal('amount_estimated', 15, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->datetime('published_at')->nullable();
            $table->datetime('tender_deadline')->nullable();
            $table->json('matched_rubros')->nullable();
            $table->string('secp_url')->nullable();
            $table->json('raw_data')->nullable();
            $table->datetime('notified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
