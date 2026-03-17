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
        Schema::create('personnel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('cedula', 20)->nullable();
            $table->date('fecha_nac')->nullable();
            $table->string('cargo')->nullable();
            $table->string('nivel_educativo')->nullable(); // bachiller, tecnico, universitario, posgrado, doctorado
            $table->string('titulo')->nullable();
            $table->string('institucion')->nullable();
            $table->unsignedSmallInteger('anio_titulo')->nullable();
            $table->json('idiomas')->nullable();   // ["Español","Inglés"]
            $table->json('skills')->nullable();    // ["AutoCAD","PMP","OSHA"]
            $table->string('photo_path')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel');
    }
};
