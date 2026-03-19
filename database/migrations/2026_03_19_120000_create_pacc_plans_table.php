<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacc_plans', function (Blueprint $table) {
            $table->id();
            $table->string('uid_pacc', 100)->unique();
            $table->string('institution_code', 50)->nullable();
            $table->string('institution_name', 255)->nullable();
            $table->smallInteger('period')->nullable();
            $table->string('version', 50)->nullable();
            $table->string('responsible', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('url', 500)->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index('institution_code');
            $table->index('period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacc_plans');
    }
};
