<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create channels table for multi-channel customer communication support.
     * Channels represent different communication methods (email, chat, phone, social, etc.)
     */
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Channel name (e.g., "Email Support", "Live Chat", "Phone")
            $table->unsignedTinyInteger('type'); // Channel type (1=email, 2=chat, 3=phone, etc.)
            $table->json('settings')->nullable(); // Channel-specific configuration
            $table->boolean('active')->default(true); // Whether channel is active
            $table->timestamps();
            
            $table->index('active');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
