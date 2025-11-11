<?php

declare(strict_types=1);

namespace Tests\Unit;

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

    public function test_creating_sets_read_by_user_when_created_by_user(): void
    {
        $conversation = Conversation::factory()->create([
            'source_via' => Conversation::PERSON_USER,
            'read_by_user' => null,
        ]);

        $this->assertTrue($conversation->read_by_user);
    }

    public function test_creating_sets_default_status(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => null,
        ]);

        $this->assertEquals(Conversation::STATUS_ACTIVE, $conversation->status);
    }

    public function test_created_increments_folder_counters(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'total_count' => 0,
            'active_count' => 0,
        ]);

        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $folder->refresh();
        $this->assertEquals(1, $folder->total_count);
        $this->assertEquals(1, $folder->active_count);
    }

    public function test_deleting_removes_threads(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $conversation->delete();

        $this->assertDatabaseMissing('threads', ['id' => $thread->id]);
    }

    public function test_deleting_detaches_followers(): void
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();
        $conversation->followers()->attach($user->id);

        $this->assertDatabaseHas('followers', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);

        $conversation->delete();

        $this->assertDatabaseMissing('followers', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);
    }
}
