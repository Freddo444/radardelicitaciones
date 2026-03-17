<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();

            $table->enum('event_type', [
                'visita_campo',
                'aclaraciones_deadline',
                'entrega_oferta',
                'apertura_sobres',
                'adjudicacion_estimada',
                'custom',
            ])->default('custom');

            $table->string('description')->nullable();
            $table->dateTime('event_date');
            $table->unsignedTinyInteger('alert_days_before')->default(3);
            $table->timestamp('alerted_at')->nullable();
            $table->enum('status', ['pending', 'completed', 'missed'])->default('pending');
            $table->timestamps();

            $table->index(['offer_id', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_events');
    }
};
