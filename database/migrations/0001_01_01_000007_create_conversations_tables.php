<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated: conversations, threads, followers, conversation_user_stars
 *
 * Merged from:
 *  - 0001_01_01_000006_create_conversations_tables.php
 *  - 2026_03_04_001156_add_sender_details_and_client_user_to_core_tables.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── conversations ───────────────────────────────────────────────
        if (! Schema::hasTable('conversations')) {
            Schema::create('conversations', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('number');
                $table->unsignedInteger('threads_count')->default(0);
                $table->unsignedTinyInteger('type');
                $table->unsignedBigInteger('folder_id');
                $table->unsignedBigInteger('mailbox_id');
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedTinyInteger('status')->default(1)->index();
                $table->unsignedTinyInteger('state')->default(1);
                $table->string('subject', 998)->nullable();
                $table->string('customer_email', 191)->nullable();
                $table->json('cc')->nullable();
                $table->json('bcc')->nullable();
                $table->string('preview', 255);
                $table->boolean('imported')->default(false);
                $table->boolean('has_attachments')->default(false);
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->unsignedBigInteger('created_by_customer_id')->nullable();
                $table->unsignedTinyInteger('source_via');
                $table->unsignedTinyInteger('source_type');
                $table->unsignedTinyInteger('channel')->nullable();
                $table->unsignedBigInteger('closed_by_user_id')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->dateTime('follow_up_date')->nullable();
                $table->dateTime('follow_up_reminded_at')->nullable();
                $table->timestamp('user_updated_at')->nullable();
                $table->timestamp('last_reply_at')->nullable()->index();
                $table->unsignedTinyInteger('last_reply_from')->nullable();
                $table->boolean('read_by_user')->default(false);
                $table->text('meta')->nullable();
                // Merged columns from sender details migration
                $table->string('sender_email')->nullable();
                $table->string('sender_name')->nullable();
                $table->unsignedBigInteger('client_user_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['folder_id', 'status']);
                $table->index(['mailbox_id', 'customer_id']);
                $table->index('state');
                $table->unique(['mailbox_id', 'number']);

                $table->foreign('folder_id')->references('id')->on('folders')->cascadeOnDelete();
                $table->foreign('mailbox_id')->references('id')->on('mailboxes')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
                $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('closed_by_user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('client_user_id')->references('id')->on('users')->nullOnDelete();
            });
        } else {
            Schema::table('conversations', function (Blueprint $table) {
                if (! Schema::hasColumn('conversations', 'sender_email')) {
                    $table->string('sender_email')->nullable()->after('meta');
                }
                if (! Schema::hasColumn('conversations', 'sender_name')) {
                    $table->string('sender_name')->nullable()->after('sender_email');
                }
                if (! Schema::hasColumn('conversations', 'client_user_id')) {
                    $table->unsignedBigInteger('client_user_id')->nullable()->after('sender_name');
                }
            });
        }

        // ── threads ─────────────────────────────────────────────────────
        if (! Schema::hasTable('threads')) {
            Schema::create('threads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id')->index();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedTinyInteger('type');
                $table->unsignedTinyInteger('status')->default(1);
                $table->unsignedTinyInteger('state')->default(1);
                $table->unsignedTinyInteger('action_type')->nullable();
                $table->string('action_data', 255)->nullable();
                $table->mediumText('body')->nullable();
                $table->text('headers')->nullable();
                $table->string('from', 191)->nullable();
                $table->text('to')->nullable();
                $table->text('cc')->nullable();
                $table->text('bcc')->nullable();
                $table->boolean('has_attachments')->default(false);
                $table->string('message_id', 760)->nullable()->index();
                $table->unsignedTinyInteger('source_via');
                $table->unsignedTinyInteger('source_type');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->unsignedBigInteger('created_by_customer_id')->nullable();
                $table->unsignedBigInteger('edited_by_user_id')->nullable();
                $table->timestamp('edited_at')->nullable();
                $table->mediumText('body_original')->nullable();
                $table->boolean('first')->default(false);
                $table->unsignedBigInteger('saved_reply_id')->nullable();
                $table->unsignedTinyInteger('send_status')->nullable();
                $table->text('send_status_data')->nullable();
                $table->string('meta_subtype', 20)->nullable();
                $table->unsignedBigInteger('meta_id')->nullable();
                $table->boolean('imported')->default(false);
                $table->timestamp('opened_at')->nullable();
                $table->text('meta')->nullable();
                // Merged columns from sender details migration
                $table->string('sender_email')->nullable();
                $table->string('sender_name')->nullable();
                $table->unsignedBigInteger('client_user_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
                $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('edited_by_user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('client_user_id')->references('id')->on('users')->nullOnDelete();
            });
        } else {
            Schema::table('threads', function (Blueprint $table) {
                if (! Schema::hasColumn('threads', 'sender_email')) {
                    $table->string('sender_email')->nullable()->after('meta');
                }
                if (! Schema::hasColumn('threads', 'sender_name')) {
                    $table->string('sender_name')->nullable()->after('sender_email');
                }
                if (! Schema::hasColumn('threads', 'client_user_id')) {
                    $table->unsignedBigInteger('client_user_id')->nullable()->after('sender_name');
                }
            });
        }

        // ── followers ───────────────────────────────────────────────────
        if (! Schema::hasTable('followers')) {
            Schema::create('followers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
                $table->unique(['conversation_id', 'user_id']);
                $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        // ── conversation_user_stars ─────────────────────────────────────
        if (! Schema::hasTable('conversation_user_stars')) {
            Schema::create('conversation_user_stars', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id');
                $table->unsignedBigInteger('user_id');
                $table->unique(['conversation_id', 'user_id']);
                $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // WARNING: down() intentionally left non-destructive.
        // This migration consolidation is forward-only.
    }
};
