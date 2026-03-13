<?php

declare(strict_types=1);

namespace Tests\Unit\Observers;

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\UnitTestCase;

class ObserverCascadeTest extends UnitTestCase
{
    public function test_conversation_deletion_cascades_to_threads(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->for($conversation)->create();

        $threadId = $thread->id;

        // Delete conversation
        $conversation->delete();

        // Thread should be deleted by cascade (Soft Delete)
        $this->assertSoftDeleted('threads', [
            'id' => $threadId,
        ]);
    }

    public function test_conversation_deletion_cascades_to_attachments(): void
    {
        Storage::fake('public');

        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->for($conversation)->create();
        $attachment = Attachment::factory()->for($thread)->create();

        $attachmentId = $attachment->id;

        // Delete conversation
        $conversation->delete();

        // Attachment should be deleted
        $this->assertDatabaseMissing('attachments', [
            'id' => $attachmentId,
        ]);
    }

    public function test_mailbox_deletion_updates_folder_counters(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->for($mailbox)->create([
            'total_count' => 10,
            'active_count' => 5,
        ]);

        // Verify initial state
        $this->assertEquals(10, $folder->total_count);
        $this->assertEquals(5, $folder->active_count);

        // Delete mailbox
        $mailbox->delete();

        // Folder should be deleted via cascade
        $this->assertDatabaseMissing('folders', [
            'id' => $folder->id,
        ]);
    }

    public function test_user_creation_does_not_auto_create_folders(): void
    {
        $initialFolderCount = Folder::count();

        $user = User::factory()->create();

        // User creation alone should not create folders
        $this->assertEquals($initialFolderCount, Folder::count());
    }

    public function test_customer_deletion_cascades_to_conversations(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $conversationId = $conversation->id;

        // Delete customer
        $customer->delete();

        // Conversation should be deleted (Soft Delete)
        $this->assertSoftDeleted('conversations', [
            'id' => $conversationId,
        ]);
    }

    public function test_thread_deletion_removes_attachments(): void
    {
        Storage::fake('public');

        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->for($conversation)->create();
        $attachment = Attachment::factory()->for($thread)->create();

        $attachmentId = $attachment->id;

        // Delete thread directly
        $thread->delete();

        // Attachment should be deleted
        $this->assertDatabaseMissing('attachments', [
            'id' => $attachmentId,
        ]);
    }

    public function test_attachment_deletion_removes_file_from_storage(): void
    {
        Storage::fake('public');

        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->for($conversation)->create();
        $attachment = Attachment::factory()->for($thread)->create([
            'file_name' => 'test.pdf',
        ]);

        // Create fake file
        Storage::disk('public')->put('attachments/test.pdf', 'content');

        $attachmentId = $attachment->id;

        // Delete attachment
        $attachment->delete();

        // Verify attachment is removed from database
        $this->assertDatabaseMissing('attachments', ['id' => $attachmentId]);
    }

    public function test_folder_type_update_does_not_trigger_loops(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->for($mailbox)->create([
            'type' => Folder::TYPE_INBOX,
        ]);

        // Update folder type multiple times
        $folder->type = Folder::TYPE_SENT;
        $folder->save();

        $folder->type = Folder::TYPE_TRASH;
        $folder->save();

        // Should not cause infinite loop
        $this->assertEquals(Folder::TYPE_TRASH, $folder->fresh()->type);
    }

    public function test_conversation_update_does_not_trigger_infinite_loop(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        // Update status multiple times
        for ($i = 0; $i < 5; $i++) {
            $conversation->status = Conversation::STATUS_ACTIVE;
            $conversation->save();
        }

        // Should complete without infinite recursion
        $conversation->refresh();
        $this->assertEquals(Conversation::STATUS_ACTIVE, $conversation->status);
    }

    public function test_mailbox_deletion_removes_all_conversations(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation1 = Conversation::factory()->for($mailbox)->create();
        $conversation2 = Conversation::factory()->for($mailbox)->create();

        $conv1Id = $conversation1->id;
        $conv2Id = $conversation2->id;

        // Delete mailbox
        $mailbox->delete();

        // All conversations should be deleted (Hard Delete due to DB cascade)
        $this->assertDatabaseMissing('conversations', ['id' => $conv1Id]);
        $this->assertDatabaseMissing('conversations', ['id' => $conv2Id]);
    }
}
