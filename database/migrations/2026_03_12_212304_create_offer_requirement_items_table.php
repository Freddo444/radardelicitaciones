<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_requirement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_requirement_id')->constrained('offer_requirements')->cascadeOnDelete();

            // Polymorphic-style vault reference (not using Laravel morph — explicit enum)
            $table->enum('vault_ref_type', [
                'vault_documents',
                'personnel',
                'projects',
                'equipment',
                'financial_records',
                'offer_generated_files',
            ]);
            $table->unsignedBigInteger('vault_ref_id');
            $table->string('role_note')->nullable(); // e.g. "director técnico", "año 2024"
            $table->timestamps();

            $table->index('offer_requirement_id');
            $table->index(['vault_ref_type', 'vault_ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_requirement_items');
    }
};
