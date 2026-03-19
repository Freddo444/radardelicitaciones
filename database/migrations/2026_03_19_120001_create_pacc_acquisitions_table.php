<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacc_acquisitions', function (Blueprint $table) {
            $table->id();
            $table->string('id_adquisicion', 100)->nullable();
            $table->string('uid_pacc', 100)->nullable();
            $table->string('institution_code', 50)->nullable();
            $table->string('institution_name', 255)->nullable();
            $table->text('description')->nullable();
            $table->text('purpose')->nullable();
            $table->date('start_date')->nullable();
            $table->string('object_type', 100)->nullable();
            $table->decimal('estimated_amount', 15, 2)->default(0);
            $table->string('currency', 10)->default('DOP');
            $table->string('modality', 100)->nullable();
            $table->boolean('mipymes')->default(false);
            $table->boolean('mipymes_mujeres')->default(false);
            $table->string('unspsc_familia', 20)->nullable();
            $table->string('unspsc_clase', 20)->nullable();
            $table->string('unspsc_subclase', 20)->nullable();
            $table->string('unspsc_description', 500)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('api_hash', 64)->unique();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index('uid_pacc');
            $table->index('institution_code');
            $table->index('modality');
            $table->index('start_date');
            $table->index('unspsc_familia');
            $table->index('unspsc_clase');
            $table->index('unspsc_subclase');
            $table->index('object_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacc_acquisitions');
    }
};
