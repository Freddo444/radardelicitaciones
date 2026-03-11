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
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->string('subclase', 12)->primary();
            $table->string('descripcion_subclase');
            $table->string('clase', 12)->index();
            $table->string('descripcion_clase');
            $table->string('familia', 12)->index();
            $table->string('descripcion_familia');
            $table->string('segmento', 12)->index();
            $table->string('descripcion_segmento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_items');
    }
};
