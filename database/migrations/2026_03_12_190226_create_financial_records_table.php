<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->smallInteger('anio_fiscal');
            $table->char('currency', 3)->default('DOP');

            // Balance sheet inputs
            $table->decimal('activo_total', 18, 2)->nullable();
            $table->decimal('activo_circulante', 18, 2)->nullable();
            $table->decimal('inventarios', 18, 2)->nullable();
            $table->decimal('pasivo_total', 18, 2)->nullable();
            $table->decimal('pasivo_circulante', 18, 2)->nullable();
            $table->decimal('patrimonio', 18, 2)->nullable();
            $table->decimal('ingresos', 18, 2)->nullable();
            $table->decimal('utilidad', 18, 2)->nullable();

            // Auto-calculated indices (stored for history, 4dp)
            $table->decimal('idx_solvencia', 10, 4)->nullable();
            $table->decimal('idx_liquidez', 10, 4)->nullable();
            $table->decimal('idx_endeudamiento', 10, 4)->nullable();
            $table->decimal('idx_capital_trabajo', 18, 2)->nullable();

            // Manual override fields
            $table->decimal('solvencia_override', 10, 4)->nullable();
            $table->decimal('liquidez_override', 10, 4)->nullable();
            $table->decimal('endeudamiento_override', 10, 4)->nullable();
            $table->decimal('capital_trabajo_override', 18, 2)->nullable();
            $table->text('override_razon')->nullable();  // reason for any override

            // Supporting documents (stored in vault disk)
            $table->string('path_ir2')->nullable();           // IR-2 / ISR declaration PDF
            $table->string('filename_ir2')->nullable();
            $table->string('path_estado_financiero')->nullable();  // certified financial statement
            $table->string('filename_estado_financiero')->nullable();

            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'anio_fiscal']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_records');
    }
};
