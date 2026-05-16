<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('newsletter_subscribed')->default(false)->after('last_sign_in_at');
            $table->timestamp('newsletter_consented_at')->nullable()->after('newsletter_subscribed');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['newsletter_subscribed', 'newsletter_consented_at']);
        });
    }
};
