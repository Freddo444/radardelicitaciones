<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('awarded_articles', function (Blueprint $table) {
            $table->id();
            $table->string('contract_code', 80)->nullable();
            $table->string('process_code', 80)->nullable();
            $table->string('unspsc_familia', 20)->nullable();
            $table->string('unspsc_clase', 20)->nullable();
            $table->string('unspsc_subclase', 20)->nullable();
            $table->string('unspsc_description', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('unit_measure', 100)->nullable();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);
            $table->string('currency', 10)->default('DOP');
            $table->string('provider_name', 255)->nullable();
            $table->string('provider_rpe', 50)->nullable();
            $table->string('institution_name', 255)->nullable();
            $table->string('institution_code', 50)->nullable();
            $table->date('award_date')->nullable();
            $table->string('api_hash', 64)->unique();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index('process_code');
            $table->index('contract_code');
            $table->index('unspsc_familia');
            $table->index('unspsc_clase');
            $table->index('unspsc_subclase');
            $table->index('provider_rpe');
            $table->index('institution_code');
            $table->index('award_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('awarded_articles');
    }
};
