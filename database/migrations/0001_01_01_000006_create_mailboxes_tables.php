<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated: mailboxes, mailbox_user, folders
 *
 * Merged from:
 *  - 0001_01_01_000003_create_mailboxes_tables.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── mailboxes ───────────────────────────────────────────────────
        if (! Schema::hasTable('mailboxes')) {
            Schema::create('mailboxes', function (Blueprint $table) {
                $table->id();
                $table->string('name', 40);
                $table->boolean('is_default')->default(false);
                $table->unsignedTinyInteger('status')->default(1)->index();
                $table->string('email', 128)->unique();
                $table->text('aliases')->nullable();
                $table->boolean('aliases_reply')->default(false);
                $table->unsignedTinyInteger('from_name')->default(1);
                $table->string('from_name_custom', 128)->nullable();
                $table->unsignedTinyInteger('ticket_status')->default(2);
                $table->unsignedTinyInteger('ticket_assignee')->default(1);
                $table->unsignedTinyInteger('template')->default(1);
                $table->text('signature')->nullable();
                $table->text('before_reply')->nullable();
                $table->unsignedTinyInteger('out_method')->default(1);
                $table->string('out_server')->nullable();
                $table->text('out_username')->nullable();
                $table->text('out_password')->nullable();
                $table->unsignedInteger('out_port')->nullable();
                $table->unsignedTinyInteger('out_encryption')->default(0);
                $table->string('in_server')->nullable();
                $table->unsignedInteger('in_port')->default(143);
                $table->string('in_username', 100)->nullable();
                $table->text('in_password')->nullable();
                $table->unsignedTinyInteger('in_protocol')->default(1);
                $table->unsignedTinyInteger('in_encryption')->default(0);
                $table->boolean('in_validate_cert')->default(true);
                $table->text('in_imap_folders')->nullable();
                $table->text('imap_sent_folder')->nullable();
                $table->boolean('auto_reply_enabled')->default(false);
                $table->string('auto_reply_subject', 128)->nullable();
                $table->text('auto_reply_message')->nullable();
                $table->string('auto_bcc')->nullable();
                $table->boolean('office_hours_enabled')->default(false);
                $table->boolean('ratings')->default(false);
                $table->unsignedTinyInteger('ratings_placement')->default(1);
                $table->text('ratings_text')->nullable();
                $table->text('meta')->nullable();
                $table->timestamps();
            });
        }

        // ── mailbox_user ────────────────────────────────────────────────
        if (! Schema::hasTable('mailbox_user')) {
            Schema::create('mailbox_user', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('mailbox_id');
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedTinyInteger('access')->default(10);
                $table->boolean('after_send')->default(true);
                $table->timestamps();

                $table->unique(['mailbox_id', 'user_id']);
                $table->foreign('mailbox_id')->references('id')->on('mailboxes')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        // ── folders ─────────────────────────────────────────────────────
        if (! Schema::hasTable('folders')) {
            Schema::create('folders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('mailbox_id');
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedTinyInteger('type');
                $table->string('name', 100)->nullable();
                $table->unsignedInteger('total_count')->default(0);
                $table->unsignedInteger('active_count')->default(0);
                $table->text('meta')->nullable();
                $table->timestamps();

                $table->index(['mailbox_id', 'type']);
                $table->foreign('mailbox_id')->references('id')->on('mailboxes')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // WARNING: down() intentionally left non-destructive.
        // This migration consolidation is forward-only.
    }
};
