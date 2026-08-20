<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('grace_ends_at')->nullable();
            $table->timestamp('payment_failed_notified_at')->nullable();
            $table->timestamp('trial_ending_notified_at')->nullable();
            $table->timestamp('trial_expired_notified_at')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('welcome_sent_at')->nullable();
            $table->timestamp('reengagement_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'grace_ends_at',
                'payment_failed_notified_at',
                'trial_ending_notified_at',
                'trial_expired_notified_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['welcome_sent_at', 'reengagement_sent_at']);
        });
    }
};
