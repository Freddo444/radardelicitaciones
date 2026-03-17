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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social');
            $table->string('nombre_comercial')->nullable();
            $table->string('rnc', 20)->nullable();
            $table->string('direccion')->nullable();
            $table->string('municipio')->nullable();
            $table->string('provincia')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('web')->nullable();
            $table->string('rep_legal_nombre')->nullable();
            $table->string('rep_legal_cedula', 20)->nullable();
            $table->string('rep_legal_cargo')->nullable();
            $table->string('rpe_numero', 50)->nullable();
            $table->date('rpe_vence')->nullable();
            $table->string('cpa_numero', 50)->nullable();
            $table->date('cpa_vence')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
