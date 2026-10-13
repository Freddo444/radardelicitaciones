<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records whether a company's RNC was found in the DGCP supplier registry
     * at setup time. Null means it was not found, or we could not reach the
     * registry — not that the company is fake, because the registry lags a new
     * incorporation by up to a couple of months. It exists so unverified
     * accounts can be reviewed rather than silently blocked.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('rnc_verified_at')->nullable()->after('rnc');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('rnc_verified_at');
        });
    }
};
