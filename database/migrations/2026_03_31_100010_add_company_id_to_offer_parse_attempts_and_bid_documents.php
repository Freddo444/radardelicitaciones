<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_parse_attempts', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')
                ->constrained()->cascadeOnDelete();
        });

        Schema::table('bid_documents', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')
                ->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('offer_parse_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('bid_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
