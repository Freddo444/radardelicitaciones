<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_notice_checks', function (Blueprint $table) {
            $table->string('notice_uid', 64)->primary();
            $table->json('unspsc');
            $table->timestamp('checked_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_notice_checks');
    }
};
