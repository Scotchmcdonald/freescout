<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('alert_type')->index(); // unusual_variance, circuit_breaker, etc.
            $table->json('channels')->nullable(); // ['email', 'slack', 'sms']
            $table->string('frequency')->default('immediate'); // immediate, daily, weekly
            $table->json('thresholds')->nullable(); // {amount: 500, count: 10}
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'alert_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_subscriptions');
    }
};
