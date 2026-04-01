<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('pending','active','past_due','cancelled','suspended','trialing') DEFAULT 'pending'");

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('cancelled_at');
            $table->unsignedSmallInteger('trial_parse_count')->default(0)->after('trial_ends_at');
            $table->unsignedSmallInteger('trial_parse_limit')->default(2)->after('trial_parse_count');
            $table->enum('billing_cycle', ['monthly', 'annual'])->default('monthly')->after('trial_parse_limit');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['trial_ends_at', 'trial_parse_count', 'trial_parse_limit', 'billing_cycle']);
        });

        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('pending','active','past_due','cancelled','suspended') DEFAULT 'pending'");
    }
};
