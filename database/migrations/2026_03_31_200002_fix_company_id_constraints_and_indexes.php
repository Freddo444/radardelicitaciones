<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix #4 — Make company_id NOT NULL on rubros and bid_watches
        Schema::table('rubros', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });

        Schema::table('bid_watches', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });

        // Fix #5 — Add missing company_id indexes
        Schema::table('settings', function (Blueprint $table) {
            $table->index('company_id');
        });

        Schema::table('bid_watches', function (Blueprint $table) {
            $table->index('company_id');
        });

        Schema::table('in_app_notifications', function (Blueprint $table) {
            $table->index('company_id');
        });

        Schema::table('notification_log', function (Blueprint $table) {
            $table->index('company_id');
        });

        Schema::table('offer_parse_attempts', function (Blueprint $table) {
            $table->index('company_id');
        });

        Schema::table('bid_documents', function (Blueprint $table) {
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('rubros', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->change();
        });

        Schema::table('bid_watches', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->change();
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
        });

        Schema::table('bid_watches', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
        });

        Schema::table('in_app_notifications', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
        });

        Schema::table('notification_log', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
        });

        Schema::table('offer_parse_attempts', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
        });

        Schema::table('bid_documents', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
        });
    }
};
