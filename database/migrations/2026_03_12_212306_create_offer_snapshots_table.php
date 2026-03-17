<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();

            // Frozen data at assembly time
            $table->json('company_snapshot');       // Company row JSON
            $table->json('personnel_snapshot');     // Selected personnel full records
            $table->json('projects_snapshot');      // Selected projects full records
            $table->json('equipment_snapshot');     // Selected equipment full records
            $table->json('financials_snapshot');    // Selected financial years + indices
            $table->json('requirements_snapshot'); // Requirements + assignments at this moment
            $table->json('file_hashes');            // sha256 + path for every vault/generated file referenced

            $table->string('zip_path')->nullable();    // assembled ZIP path
            $table->char('zip_sha256', 64)->nullable();
            $table->timestamp('assembled_at');
            $table->foreignId('assembled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('offer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_snapshots');
    }
};
