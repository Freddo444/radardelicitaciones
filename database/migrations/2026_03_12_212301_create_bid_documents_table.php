<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bid_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->enum('document_type', ['pliego', 'bases', 'terminos_ref', 'adenda', 'aclaracion', 'otros'])->default('pliego');
            $table->string('original_filename');
            $table->string('source_url')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->char('sha256', 64)->nullable();
            $table->string('local_path');               // under storage/app/bid_docs/
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->timestamps();

            $table->index('offer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bid_documents');
    }
};
