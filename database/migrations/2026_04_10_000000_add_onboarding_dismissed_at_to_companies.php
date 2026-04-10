<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('onboarding_dismissed_at')->nullable()->after('logo_path');
        });

        // Existing companies skip onboarding
        DB::table('companies')->update(['onboarding_dismissed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('onboarding_dismissed_at');
        });
    }
};
