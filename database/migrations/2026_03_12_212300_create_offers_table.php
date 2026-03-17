<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('bid_id')->nullable()->constrained('bids')->nullOnDelete();

            // Denormalized bid info (survives even if bid is deleted)
            $table->string('proceso_codigo')->nullable();
            $table->string('proceso_nombre')->nullable();
            $table->string('entidad_nombre')->nullable();
            $table->dateTime('fecha_limite')->nullable();

            $table->enum('estado', ['borrador', 'en_preparacion', 'listo', 'enviado'])->default('borrador');
            $table->timestamp('enviado_at')->nullable();

            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'estado']);
            $table->index('bid_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
