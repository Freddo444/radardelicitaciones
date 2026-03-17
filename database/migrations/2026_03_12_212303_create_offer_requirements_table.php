<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->foreignId('parse_attempt_id')->nullable()->constrained('offer_parse_attempts')->nullOnDelete();

            $table->string('descripcion');
            $table->enum('tipo', ['documento', 'financiero', 'personal', 'equipo', 'experiencia', 'formato', 'otro'])
                ->default('documento');
            $table->enum('estado', ['PENDIENTE', 'CUMPLE', 'NO_CUMPLE', 'ACEPTADO'])->default('PENDIENTE');
            $table->enum('source', ['gemini', 'manual'])->default('manual');
            $table->boolean('superseded')->default(false); // old gemini rows after re-parse
            $table->text('notes')->nullable();
            $table->text('acceptance_reason')->nullable(); // when estado = ACEPTADO
            $table->timestamps();

            $table->index(['offer_id', 'superseded']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_requirements');
    }
};
