<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_code', 100)->unique();
            $table->string('process_code', 80)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('provider_name', 255)->nullable();
            $table->string('provider_rpe', 50)->nullable();
            $table->string('institution_name', 255)->nullable();
            $table->string('institution_code', 50)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency', 10)->default('DOP');
            $table->string('payment_method', 100)->nullable();
            $table->string('payment_terms', 255)->nullable();
            $table->text('description')->nullable();
            $table->date('award_date')->nullable();
            $table->date('contract_date')->nullable();
            $table->string('url', 500)->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index('process_code');
            $table->index('provider_rpe');
            $table->index('institution_code');
            $table->index('status');
            $table->index('award_date');
            $table->index('contract_date');
            $table->index('amount');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
