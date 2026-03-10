<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated: attachments, activity_log, module_activity_logs, send_logs,
 *               subscriptions, notifications
 *
 * Merged from:
 *  - 0001_01_01_000007_create_attachments_and_logs_tables.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── attachments ─────────────────────────────────────────────────
        if (! Schema::hasTable('attachments')) {
            Schema::create('attachments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('thread_id')->index();
                $table->unsignedBigInteger('conversation_id')->nullable()->index();
                $table->string('file_name', 255);
                $table->string('file_dir', 255);
                $table->unsignedInteger('file_size');
                $table->string('mime_type', 100)->nullable();
                $table->boolean('embedded')->default(false);
                $table->timestamps();
                $table->foreign('thread_id')->references('id')->on('threads')->cascadeOnDelete();
                $table->foreign('conversation_id')->references('id')->on('conversations')->nullOnDelete();
            });
        }

        // ── activity_log ────────────────────────────────────────────────
        if (! Schema::hasTable('activity_log')) {
            Schema::create('activity_log', function (Blueprint $table) {
                $table->id();
                $table->string('log_name')->nullable()->index();
                $table->text('description');
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->string('subject_type')->nullable();
                $table->string('event')->nullable();
                $table->unsignedBigInteger('causer_id')->nullable();
                $table->string('causer_type')->nullable();
                $table->json('properties')->nullable();
                $table->string('batch_uuid')->nullable();
                $table->timestamps();
                $table->index('created_at');
            });
        }

        // ── module_activity_logs ────────────────────────────────────────
        if (! Schema::hasTable('module_activity_logs')) {
            Schema::create('module_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('module_name', 100)->index();
                $table->string('action', 50)->index();
                $table->text('metadata')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
                $table->index(['module_name', 'action']);
                $table->index('created_at');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // ── send_logs ───────────────────────────────────────────────────
        if (! Schema::hasTable('send_logs')) {
            Schema::create('send_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('thread_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('message_id', 998)->nullable();
                $table->string('email', 191)->index();
                $table->string('subject', 255)->nullable();
                $table->unsignedTinyInteger('mail_type')->nullable();
                $table->unsignedTinyInteger('status')->index();
                $table->text('status_message')->nullable();
                $table->string('smtp_queue_id', 100)->nullable();
                $table->unsignedInteger('opens')->default(0);
                $table->unsignedInteger('clicks')->default(0);
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('clicked_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->foreign('thread_id')->references('id')->on('threads')->nullOnDelete();
                $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });

            // Custom partial index for message_id (MySQL 191 char prefix)
            try {
                if (config('database.default') !== 'sqlite') {
                    \Illuminate\Support\Facades\DB::statement(
                        'CREATE INDEX send_logs_message_id_index ON send_logs (message_id(191))'
                    );
                }
            } catch (\Throwable) {
                // Index may already exist or DB doesn't support prefix indexes
            }
        }

        // ── subscriptions ───────────────────────────────────────────────
        if (! Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedTinyInteger('medium');
                $table->unsignedTinyInteger('event');
                $table->timestamps();
                $table->unique(['user_id', 'medium', 'event']);
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        // ── notifications ───────────────────────────────────────────────
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->unsignedBigInteger('notifiable_id');
                $table->string('notifiable_type');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // WARNING: down() intentionally left non-destructive.
        // This migration consolidation is forward-only.
    }
};
