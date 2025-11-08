<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Attachments - files attached to conversations/threads
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_dir', 20)->nullable(); // examples: 1/2, 1/2/3
            $table->string('file_name', 255);
            $table->string('mime_type', 127);
            $table->unsignedInteger('type');
            $table->unsignedInteger('size')->nullable();
            $table->boolean('embedded')->default(false);

            // Indexes
            $table->index(['thread_id', 'embedded']);
        });

        // Activity logs - audit trail of all actions
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->string('batch_uuid')->nullable();
            $table->timestamps();

            $table->index('log_name');
            $table->index('created_at');
        });

        // Send logs - tracking outgoing emails
        Schema::create('send_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('message_id', 998)->nullable();
            $table->string('email', 191);
            $table->unsignedTinyInteger('mail_type');
            $table->unsignedTinyInteger('status'); // 1=sent, 2=failed
            $table->string('status_message', 255)->nullable();
            $table->string('smtp_queue_id', 100)->nullable();
            $table->timestamps();

            $table->index('thread_id');
            $table->index('email');
            $table->index('status');
            $table->index(['customer_id', 'mail_type', 'created_at']);
        });

        // Add prefix index for message_id (MySQL requires prefix for TEXT columns)
        // SQLite: Skip this - it handles TEXT indexing differently
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('CREATE INDEX send_logs_message_id_index ON send_logs (message_id(191))');
        }

        // Subscriptions - users subscribing to receive notifications
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('medium'); // 1=email, 2=browser, etc.
            $table->unsignedTinyInteger('event'); // specific event type
            $table->timestamps();

            $table->unique(['user_id', 'medium', 'event']);
        });

        // Notifications - Laravel notifications table
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('send_logs');
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('attachments');
    }
};
