<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prellenado_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('form_selections');
            $table->json('resource_selections')->nullable();
            $table->json('articles_data')->nullable();
            $table->string('zip_path')->nullable();
            $table->string('zip_sha256')->nullable();
            $table->timestamps();
        });

        Schema::table('offer_generated_files', function (Blueprint $table) {
            $table->foreignId('prellenado_package_id')->nullable()->after('offer_id')
                ->constrained('prellenado_packages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('offer_generated_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prellenado_package_id');
        });

        Schema::dropIfExists('prellenado_packages');
    }
};
