<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rubros', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')
                ->constrained()->cascadeOnDelete();

            $table->dropUnique(['code']);
            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('rubros', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
            $table->dropConstrainedForeignId('company_id');
            $table->unique('code');
        });
    }
};
