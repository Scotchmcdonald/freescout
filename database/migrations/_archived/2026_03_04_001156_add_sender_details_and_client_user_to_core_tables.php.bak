<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'sender_email')) {
                $table->string('sender_email')->nullable()->after('subject');
            }
            if (!Schema::hasColumn('conversations', 'sender_name')) {
                $table->string('sender_name')->nullable()->after('sender_email');
            }
            if (!Schema::hasColumn('conversations', 'client_user_id')) {
                $table->foreignId('client_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('threads', function (Blueprint $table) {
            if (!Schema::hasColumn('threads', 'sender_email')) {
                $table->string('sender_email')->nullable()->after('type');
            }
            if (!Schema::hasColumn('threads', 'sender_name')) {
                $table->string('sender_name')->nullable()->after('sender_email');
            }
            if (!Schema::hasColumn('threads', 'client_user_id')) {
                $table->foreignId('client_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        // Don't need strict reverse logic right now
    }
};
