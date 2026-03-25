<?php

declare(strict_types=1);

namespace Tests\Integration\Actions;

use App\Actions\MergeConversationsAction;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeConversationsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_rejects_merge_for_different_mailboxes(): void
    {
        $source = Conversation::factory()->create(['mailbox_id' => Mailbox::factory()->create()->id]);
        $target = Conversation::factory()->create(['mailbox_id' => Mailbox::factory()->create()->id]);
        $user = User::factory()->create();

        $result = (new MergeConversationsAction)->execute($source, $target, $user);

        $this->assertFalse($result['success']);
        $this->assertSame('Conversations must be in the same mailbox', $result['message']);
    }

    public function test_execute_merges_threads_creates_note_and_marks_source_deleted(): void
    {
        $mailbox = Mailbox::factory()->create();
        $source = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'number' => 101,
            'state' => Conversation::STATE_PUBLISHED,
        ]);
        $target = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'number' => 102,
            'state' => Conversation::STATE_PUBLISHED,
            'last_reply_at' => now()->subDay(),
        ]);
        $user = User::factory()->create();

        $sourceThread = Thread::factory()->create([
            'conversation_id' => $source->id,
            'type' => Thread::TYPE_MESSAGE,
            'created_at' => now()->subHour(),
        ]);
        Thread::factory()->create([
            'conversation_id' => $target->id,
            'type' => Thread::TYPE_CUSTOMER,
            'created_at' => now(),
        ]);

        $result = (new MergeConversationsAction)->execute($source, $target, $user);

        $this->assertTrue($result['success']);
        $this->assertSame("Conversation #101 merged into #102", $result['message']);
        $this->assertSame($target->id, $sourceThread->fresh()->conversation_id);
        $this->assertSame(Conversation::STATE_DELETED, $source->fresh()->state);
        $this->assertSame($target->id, $source->fresh()->meta['merged_into_id']);
        $this->assertDatabaseHas('threads', [
            'conversation_id' => $target->id,
            'type' => Thread::TYPE_NOTE,
            'created_by_user_id' => $user->id,
            'body' => 'Merged conversation #101 into this conversation.',
        ]);
    }
}