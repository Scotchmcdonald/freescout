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
        // Ensure fresh schema by dropping conflicting table from schema dump
        Schema::dropIfExists('google_push_channels');

        // Only create if it doesn't exist (prevents conflicts in test environments)
        if (!Schema::hasTable('google_push_channels')) {
            Schema::create('google_push_channels', function (Blueprint $table) {
            $table->id();
            
            // Resource information
            $table->string('resource_type'); // users, groups, orgunits, chrome_devices
            $table->string('resource_id'); // Google's resource ID
            
            // Channel information
            $table->string('channel_id')->unique(); // Our channel ID (UUID)
            $table->string('token')->nullable(); // Verification token
            $table->string('webhook_url'); // The URL Google sends notifications to
            
            // Status tracking
            $table->timestamp('expiration_time'); // When channel expires
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_notification_at')->nullable();
            $table->integer('notification_count')->default(0);
            
            // Additional metadata
            $table->json('metadata')->nullable(); // Store extra info (created_via, errors, etc.)
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('channel_id');
            $table->index('resource_type');
            $table->index(['is_active', 'expiration_time']); // For finding expiring channels
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_push_channels');
    }
};
