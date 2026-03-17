<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // offer_personnel
        Schema::create('offer_personnel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
            $table->string('role_note')->nullable(); // e.g. "Director Técnico"
            $table->timestamps();
            $table->unique(['offer_id', 'personnel_id']);
        });

        // offer_projects
        Schema::create('offer_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('role_note')->nullable();
            $table->timestamps();
            $table->unique(['offer_id', 'project_id']);
        });

        // offer_equipment
        Schema::create('offer_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->string('role_note')->nullable();
            $table->timestamps();
            $table->unique(['offer_id', 'equipment_id']);
        });

        // offer_financials
        Schema::create('offer_financials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->foreignId('financial_record_id')->constrained('financial_records')->cascadeOnDelete();
            $table->string('role_note')->nullable(); // e.g. "Año fiscal 2024"
            $table->timestamps();
            $table->unique(['offer_id', 'financial_record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_financials');
        Schema::dropIfExists('offer_equipment');
        Schema::dropIfExists('offer_projects');
        Schema::dropIfExists('offer_personnel');
    }
};
