<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add follow-up reminder fields to conversations table.
 *
 * This migration adds support for follow-up reminders on conversations.
 * When a user replies to a conversation, they can set a follow-up date
 * to be reminded later. The system will send email/database notifications
 * when the follow-up date is reached.
 *
 * Fields:
 * - follow_up_date: When the user should be reminded about this conversation
 * - follow_up_reminded_at: Timestamp of when the reminder was sent (prevents duplicates)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dateTime('follow_up_date')->nullable()->after('closed_at');
            $table->dateTime('follow_up_reminded_at')->nullable()->after('follow_up_date');
            
            // Add composite index for efficient querying of due follow-ups
            $table->index(['follow_up_date', 'follow_up_reminded_at', 'status'], 'idx_follow_up_reminders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('idx_follow_up_reminders');
            $table->dropColumn(['follow_up_date', 'follow_up_reminded_at']);
        });
    }
};
