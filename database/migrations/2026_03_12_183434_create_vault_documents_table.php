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
        Schema::create('vault_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('category'); // legal, habilitaciones, tributario, seguridad_social, corporativo
            $table->string('name');
            $table->string('filename');
            $table->string('path');
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->text('notes')->nullable();
            // Extended metadata
            $table->string('issuer')->nullable();
            $table->string('document_number')->nullable();
            $table->string('signed_by')->nullable();
            $table->boolean('notarized')->default(false);
            $table->enum('copy_type', ['original', 'copia', 'copia_certificada', 'apostilla'])->default('original');
            $table->string('language', 10)->default('es');
            $table->json('tags')->nullable();
            $table->enum('source_type', ['company', 'person', 'project', 'equipment'])->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->boolean('internal_only')->default(false);
            // Versioning
            $table->foreignId('replaces_document_id')->nullable()->constrained('vault_documents')->nullOnDelete();
            $table->timestamp('superseded_at')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_current']);
            $table->index(['company_id', 'category', 'is_current']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vault_documents');
    }
};
