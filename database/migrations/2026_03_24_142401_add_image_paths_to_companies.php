<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('firma_path')->nullable()->after('cpa_vence');
            $table->string('sello_path')->nullable()->after('firma_path');
            $table->string('logo_path')->nullable()->after('sello_path');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['firma_path', 'sello_path', 'logo_path']);
        });
    }
};
