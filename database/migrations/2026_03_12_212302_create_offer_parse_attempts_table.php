<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_parse_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->foreignId('bid_document_id')->nullable()->constrained('bid_documents')->nullOnDelete();

            $table->enum('status', ['pending', 'running', 'parsed', 'needs_review', 'verified', 'failed'])
                ->default('pending');
            $table->unsignedTinyInteger('confidence_score')->nullable();  // 0–100
            $table->string('parser_version')->default('v1.0');

            $table->longText('raw_extraction')->nullable();   // verbatim Gemini response
            $table->json('parsed_json')->nullable();           // post-processed structured output
            $table->text('failure_reason')->nullable();

            $table->timestamp('human_verified_at')->nullable();
            $table->foreignId('human_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['offer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_parse_attempts');
    }
};
