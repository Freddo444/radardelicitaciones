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
            $table->string('rep_legal_nacionalidad', 100)->nullable()->after('rep_legal_cargo');
            $table->string('rep_legal_estado_civil', 50)->nullable()->after('rep_legal_nacionalidad');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['rep_legal_nacionalidad', 'rep_legal_estado_civil']);
        });
    }
};
