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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('cliente');                          // entidad contratante
            $table->string('numero_contrato')->nullable();
            $table->decimal('monto', 18, 2)->nullable();
            $table->char('currency', 3)->default('DOP');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('unspsc_codigo', 20)->nullable();   // UNSPSC rubro code
            $table->string('contacto_cliente')->nullable();    // contact name at client
            $table->string('contacto_telefono', 30)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'fecha_inicio']);
            $table->index('unspsc_codigo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
