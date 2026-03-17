<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_generated_files', function (Blueprint $table) {
            $table->foreign('offer_id')->references('id')->on('offers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('offer_generated_files', function (Blueprint $table) {
            $table->dropForeign(['offer_id']);
        });
    }
};
