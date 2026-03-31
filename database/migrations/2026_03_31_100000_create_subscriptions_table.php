<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('plan', ['basic', 'custom'])->default('basic');
            $table->enum('status', ['pending', 'active', 'past_due', 'cancelled', 'suspended'])->default('pending');
            $table->date('current_period_start')->nullable();
            $table->date('current_period_end')->nullable();
            $table->unsignedSmallInteger('max_companies')->default(1);
            $table->unsignedSmallInteger('max_users')->default(2);
            $table->decimal('monthly_amount', 8, 2)->default(45.00);
            $table->enum('payment_gateway', ['paypal', 'azul', 'bank_transfer'])->nullable();
            $table->string('gateway_subscription_id')->nullable();
            $table->string('gateway_customer_id')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
