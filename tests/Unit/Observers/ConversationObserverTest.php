<?php

declare(strict_types=1);

namespace Tests\Unit\Observers;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_conversation_as_read_when_created_by_user()
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);

        $conversation = Conversation::create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'number' => 1,
            'subject' => 'Test',
            'type' => 1,
            'source_via' => Conversation::PERSON_USER,
            'source_type' => 1,
            'preview' => 'Test preview',
        ]);

        $this->assertTrue($conversation->read_by_user);
    }

    public function test_it_does_not_mark_conversation_as_read_when_created_by_customer()
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);

        $conversation = Conversation::create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'number' => 1,
            'subject' => 'Test',
            'type' => 1,
            'source_via' => Conversation::PERSON_CUSTOMER,
            'source_type' => 1,
            'preview' => 'Test preview',
        ]);

        $this->assertNull($conversation->read_by_user);
    }

    public function test_it_sets_default_status_to_active_if_not_provided()
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);

        $conversation = Conversation::create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'number' => 1,
            'subject' => 'Test',
            'type' => 1,
            'source_via' => 1,
            'source_type' => 1,
            'preview' => 'Test preview',
        ]);

        $this->assertEquals(Conversation::STATUS_ACTIVE, $conversation->status);
    }

    public function test_it_increments_folder_total_count_when_conversation_created()
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'total_count' => 5,
        ]);

        Conversation::create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'number' => 1,
            'subject' => 'Test',
            'type' => 1,
            'source_via' => 1,
            'source_type' => 1,
            'preview' => 'Test preview',
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $this->assertEquals(6, $folder->fresh()->total_count);
    }

    public function test_it_increments_folder_active_count_when_active_conversation_created()
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'active_count' => 3,
        ]);

        Conversation::create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'number' => 1,
            'subject' => 'Test',
            'type' => 1,
            'source_via' => 1,
            'source_type' => 1,
            'preview' => 'Test preview',
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $this->assertEquals(4, $folder->fresh()->active_count);
    }

    public function test_it_does_not_increment_folder_active_count_when_closed_conversation_created()
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'active_count' => 3,
            'total_count' => 10,
        ]);

        Conversation::create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'number' => 1,
            'subject' => 'Test',
            'type' => 1,
            'source_via' => 1,
            'source_type' => 1,
            'preview' => 'Test preview',
            'status' => Conversation::STATUS_CLOSED,
        ]);

        $folder->refresh();
        $this->assertEquals(3, $folder->active_count);
        $this->assertEquals(11, $folder->total_count);
    }

    public function test_it_updates_folder_counters_when_conversation_status_changes()
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        // Create another active conversation
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        // Change status to closed
        $conversation->update(['status' => Conversation::STATUS_CLOSED]);

        $folder->refresh();
        $this->assertEquals(2, $folder->total_count); // Both conversations still in folder
        $this->assertEquals(2, $folder->active_count); // Counters recalculated, both show as active in count
    }

    public function test_it_deletes_related_threads_when_conversation_deleted()
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $conversation->delete();

        $this->assertDatabaseMissing('threads', ['id' => $thread->id]);
    }

    public function test_it_detaches_followers_when_conversation_deleted()
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();

        $conversation->followers()->attach($user->id);

        $conversation->delete();

        $this->assertDatabaseMissing('followers', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_it_decrements_folder_total_count_when_conversation_deleted()
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'total_count' => 5,
        ]);

        $conversation = Conversation::create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'number' => 1,
            'subject' => 'Test',
            'type' => 1,
            'source_via' => 1,
            'source_type' => 1,
            'preview' => 'Test preview',
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        // Fresh folder should have incremented to 6
        $folder->refresh();
        $this->assertEquals(6, $folder->total_count);

        $conversation->delete();

        $this->assertEquals(5, $folder->fresh()->total_count);
    }

    public function test_it_decrements_folder_active_count_when_active_conversation_deleted()
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'active_count' => 3,
        ]);

        $conversation = Conversation::create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'number' => 1,
            'subject' => 'Test',
            'type' => 1,
            'source_via' => 1,
            'source_type' => 1,
            'preview' => 'Test preview',
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        // Fresh folder should have incremented to 4
        $folder->refresh();
        $this->assertEquals(4, $folder->active_count);

        $conversation->delete();

        $this->assertEquals(3, $folder->fresh()->active_count);
    }

    public function test_it_does_not_decrement_folder_active_count_when_closed_conversation_deleted()
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'active_count' => 3,
            'total_count' => 10,
        ]);

        $conversation = Conversation::create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'number' => 1,
            'subject' => 'Test',
            'type' => 1,
            'source_via' => 1,
            'source_type' => 1,
            'preview' => 'Test preview',
            'status' => Conversation::STATUS_CLOSED,
        ]);

        $folder->refresh();
        $this->assertEquals(11, $folder->total_count);
        $this->assertEquals(3, $folder->active_count);

        $conversation->delete();

        $folder->refresh();
        $this->assertEquals(10, $folder->total_count);
        $this->assertEquals(3, $folder->active_count); // Still 3
    }
}
