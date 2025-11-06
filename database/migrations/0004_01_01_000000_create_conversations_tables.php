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
        // Conversations - ticket/conversation threads
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->unique(); // human-readable conversation number
            $table->unsignedInteger('threads_count')->default(0);
            $table->unsignedTinyInteger('type'); // 1=email, 2=phone, 3=chat
            $table->foreignId('folder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mailbox_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // assignee
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('status')->default(1); // 1=active, 2=pending, 3=closed, 4=spam
            $table->unsignedTinyInteger('state')->default(1); // 1=draft, 2=published, 3=deleted
            $table->string('subject', 998)->nullable();
            $table->string('customer_email', 191)->nullable();
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('preview', 255);
            $table->boolean('imported')->default(false);
            $table->boolean('has_attachments')->default(false);

            // Who created the conversation
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('created_by_customer_id')->nullable();

            // Source tracking
            $table->unsignedTinyInteger('source_via'); // 1=user, 2=customer
            $table->unsignedTinyInteger('source_type'); // 1=email, 2=web, 3=API
            $table->unsignedTinyInteger('channel')->nullable(); // multichannel support

            // Closing info
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();

            // Activity timestamps
            $table->timestamp('user_updated_at')->nullable();
            $table->timestamp('last_reply_at')->nullable();
            $table->unsignedTinyInteger('last_reply_from')->nullable();

            $table->boolean('read_by_user')->default(false);
            $table->text('meta')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['folder_id', 'status']);
            $table->index(['mailbox_id', 'customer_id']);
            $table->index('user_id');
            $table->index('state');
            $table->index('last_reply_at');
        });

        // Threads - individual messages/replies within a conversation
        Schema::create('threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // assigned user
            $table->unsignedTinyInteger('type'); // 1=message, 2=note, 3=lineitem
            $table->unsignedTinyInteger('status')->default(1); // 1=active, 2=draft, 3=deleted
            $table->unsignedTinyInteger('state')->default(1); // 1=draft, 2=published, 3=deleted
            $table->unsignedTinyInteger('action_type')->nullable();
            $table->string('action_data', 255)->nullable();
            $table->mediumText('body')->nullable();
            $table->text('headers')->nullable();
            $table->string('from', 191)->nullable();
            $table->text('to')->nullable(); // JSON
            $table->text('cc')->nullable(); // JSON
            $table->text('bcc')->nullable(); // JSON
            $table->boolean('has_attachments')->default(false);
            $table->string('message_id', 998)->nullable();
            $table->unsignedTinyInteger('source_via'); // 1=user, 2=customer
            $table->unsignedTinyInteger('source_type'); // 1=email, 2=web, 3=API
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('created_by_customer_id')->nullable();
            $table->foreignId('edited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
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
            $table->timestamps();

            $table->index('conversation_id');
            $table->index('user_id');
            $table->index(['conversation_id', 'type', 'from', 'customer_id']);
            $table->index(['conversation_id', 'created_at']);
            $table->index(['meta_subtype', 'meta_id']);
        });

        // Add unique prefix index for message_id (MySQL uses prefix for TEXT columns)
        // SQLite: Skip this - it handles TEXT indexing differently
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX threads_message_id_unique ON threads (message_id(191))');
        }

        // Conversation-Folder pivot (for organizing conversations)
        Schema::create('conversation_folder', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['conversation_id', 'folder_id']);
        });

        // Followers - users following conversations
        Schema::create('followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('followers');
        Schema::dropIfExists('conversation_folder');
        Schema::dropIfExists('threads');
        Schema::dropIfExists('conversations');
    }
};
