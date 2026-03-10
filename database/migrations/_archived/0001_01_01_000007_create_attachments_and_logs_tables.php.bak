<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Attachments
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_name', 255);
            $table->string('file_dir', 255);
            $table->unsignedInteger('file_size');
            $table->string('mime_type', 100)->nullable();
            $table->boolean('embedded')->default(false);
            $table->timestamps();

            $table->index('thread_id');
            $table->index('conversation_id');
        });

        // Activity logs (General)
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

        // Module Activity Logs (Specific to Module Management)
        Schema::create('module_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('module_name', 100)->index();
            $table->string('action', 50)->index(); // install, update, enable, disable, delete
            $table->text('metadata')->nullable(); 
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            
            $table->index(['module_name', 'action']);
            $table->index('created_at');
        });

        // Send logs
        Schema::create('send_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('message_id', 998)->nullable();
            $table->string('email', 191);
            $table->string('subject', 255)->nullable();
            $table->unsignedTinyInteger('mail_type')->nullable();
            $table->unsignedTinyInteger('status'); // 1=sent, 2=failed
            $table->text('status_message')->nullable();
            $table->string('smtp_queue_id', 100)->nullable();
            $table->unsignedInteger('opens')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('thread_id');
            $table->index('email');
            $table->index('status');
            $table->index('customer_id');
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('CREATE INDEX send_logs_message_id_index ON send_logs (message_id(191))');
        }

        // Subscriptions
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('medium'); // 1=email, 2=browser
            $table->unsignedTinyInteger('event'); 
            $table->timestamps();

            $table->unique(['user_id', 'medium', 'event']);
        });

        // Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('send_logs');
        Schema::dropIfExists('module_activity_logs');
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('attachments');
    }
};
