<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // When the "finish setting up your company" nudge was sent, so it
            // only goes out once per user.
            $table->timestamp('setup_reminder_sent_at')->nullable()->after('last_sign_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('setup_reminder_sent_at');
        });
    }
};
