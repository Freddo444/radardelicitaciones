<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A tombstone recording that a given RNC has already used its free trial.
     * It deliberately outlives the company and its data: after a purge this is
     * the only thing left, and it is what stops the same business signing up
     * again under a new email for another trial.
     */
    public function up(): void
    {
        Schema::create('trial_claims', function (Blueprint $table) {
            $table->id();
            $table->string('rnc', 20)->unique();
            $table->string('razon_social')->nullable();
            $table->foreignId('claimed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at');
            $table->timestamp('purged_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_claims');
    }
};
