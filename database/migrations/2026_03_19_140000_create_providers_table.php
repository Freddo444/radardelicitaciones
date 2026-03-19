<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('rpe', 50)->unique();
            $table->string('razon_social', 255)->nullable();
            $table->string('rnc', 50)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('tipo_persona', 50)->nullable();
            $table->boolean('is_mipyme')->default(false);
            $table->string('classification', 100)->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('province', 100)->nullable();
            $table->string('municipality', 100)->nullable();
            $table->string('contact_name', 255)->nullable();
            $table->string('contact_position', 255)->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('province');
            $table->index('is_mipyme');
            $table->index('tipo_persona');
            $table->index('rnc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
