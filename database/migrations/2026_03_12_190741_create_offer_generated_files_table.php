<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_generated_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offer_id')->nullable();  // FK to offers added in M8 migration
            $table->string('form_code');                    // e.g. SNCC.F.033, SNCC.D.045
            $table->json('source_context_json');            // snapshot of data used at generation time
            $table->string('path');                         // storage path under generated/ disk
            $table->char('sha256', 64);                     // file hash for integrity
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('supersedes_id')->nullable()->constrained('offer_generated_files')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['offer_id', 'form_code']);
            $table->index('form_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_generated_files');
    }
};
