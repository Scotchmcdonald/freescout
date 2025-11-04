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
        // Mailboxes - shared email inboxes
        Schema::create('mailboxes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->string('email', 128)->unique();
            $table->text('aliases')->nullable(); // changed from string(255) to text
            $table->boolean('aliases_reply')->default(false);
            $table->unsignedTinyInteger('from_name')->default(1); // 1=mailbox, 2=user, 3=custom
            $table->string('from_name_custom', 128)->nullable();
            $table->unsignedTinyInteger('ticket_status')->default(2); // 1=active, 2=pending, 3=closed
            $table->unsignedTinyInteger('ticket_assignee')->default(1);
            $table->unsignedTinyInteger('template')->default(1);
            $table->text('signature')->nullable();
            $table->text('before_reply')->nullable();

            // Outgoing mail (SMTP)
            $table->unsignedTinyInteger('out_method')->default(1); // 1=PHP mail, 2=Sendmail, 3=SMTP
            $table->string('out_server')->nullable();
            $table->text('out_username')->nullable(); // changed from string to text
            $table->text('out_password')->nullable();
            $table->unsignedInteger('out_port')->nullable();
            $table->unsignedTinyInteger('out_encryption')->default(0); // 0=none, 1=SSL, 2=TLS

            // Incoming mail (IMAP)
            $table->string('in_server')->nullable();
            $table->unsignedInteger('in_port')->default(143);
            $table->string('in_username', 100)->nullable();
            $table->text('in_password')->nullable();
            $table->unsignedTinyInteger('in_protocol')->default(1); // 1=IMAP, 2=POP3
            $table->unsignedTinyInteger('in_encryption')->default(0);
            $table->boolean('in_validate_cert')->default(true);
            $table->text('in_imap_folders')->nullable();
            $table->text('imap_sent_folder')->nullable(); // changed from string to text

            // Auto-reply
            $table->boolean('auto_reply_enabled')->default(false);
            $table->string('auto_reply_subject', 128)->nullable();
            $table->text('auto_reply_message')->nullable();
            $table->string('auto_bcc')->nullable();

            // Features
            $table->boolean('office_hours_enabled')->default(false);
            $table->boolean('ratings')->default(false);
            $table->unsignedTinyInteger('ratings_placement')->default(1);
            $table->text('ratings_text')->nullable();

            // Metadata
            $table->text('meta')->nullable();

            $table->timestamps();
        });

        // Mailbox-User pivot (many-to-many relationship)
        Schema::create('mailbox_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailbox_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('access')->default(10); // 10=view, 20=reply, 30=admin
            $table->boolean('after_send')->default(true);
            $table->timestamps();

            $table->unique(['mailbox_id', 'user_id']);
            $table->index('user_id');
        });

        // Folders (system and custom folders for organizing conversations)
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailbox_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('type'); // 1=assigned, 2=unassigned, 3=drafts, 4=deleted, 20=mine, 25=starred, 30=spam, 100=custom
            $table->string('name', 100)->nullable(); // for custom folders
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('active_count')->default(0);
            $table->text('meta')->nullable();
            $table->timestamps();

            $table->index(['mailbox_id', 'type']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folders');
        Schema::dropIfExists('mailbox_user');
        Schema::dropIfExists('mailboxes');
    }
};
