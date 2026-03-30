<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('rpe_vence');
            $table->string('registro_mercantil', 50)->nullable()->after('rpe_numero');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->date('rpe_vence')->nullable()->after('rpe_numero');
            $table->dropColumn('registro_mercantil');
        });
    }
};
