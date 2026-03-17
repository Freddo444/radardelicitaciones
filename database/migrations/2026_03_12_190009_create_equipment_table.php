<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('descripcion');
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->smallInteger('anio')->nullable();         // manufacture year
            $table->enum('tenencia', ['propio', 'arrendado', 'leasing'])->default('propio');
            $table->string('capacidad')->nullable();          // free-text technical spec
            $table->enum('condicion', ['bueno', 'regular', 'malo'])->default('bueno');
            $table->unsignedInteger('cantidad')->default(1);
            $table->text('notas')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
