<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Cover/separator design theme for assembled sobres.
            $table->string('sobre_theme', 32)->default('corporativo')->after('logo_path');
            // Hex accent color (e.g. #1e40af) driving the theme. Null = theme default.
            $table->string('sobre_accent_color', 9)->nullable()->after('sobre_theme');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['sobre_theme', 'sobre_accent_color']);
        });
    }
};
