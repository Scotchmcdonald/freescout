<?php

declare(strict_types=1);

namespace Tests\Integration\Misc;

use App\Misc\Draft;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DraftTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Mailbox $mailbox;
    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->mailbox = Mailbox::factory()->create();

        $this->conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();
    }

    public function test_save_creates_new_draft(): void
    {
        $data = [
            'conversation_id' => $this->conversation->id,
            'body' => 'This is a draft message',
            'to' => ['customer@example.com'],
            'cc' => [],
            'bcc' => [],
        ];

        $thread = Draft::save($data, $this->user);

        $this->assertNotNull($thread);
        $this->assertEquals('This is a draft message', $thread->body);
        $this->assertEquals(Thread::STATE_DRAFT, $thread->state);
        $this->assertEquals($this->user->id, $thread->created_by_user_id);
    }

    public function test_save_updates_existing_draft_by_thread_id(): void
    {
        $existingDraft = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
            'created_by_user_id' => $this->user->id,
            'state' => Thread::STATE_DRAFT,
            'body' => 'Original body',
        ]);

        $data = [
            'thread_id' => $existingDraft->id,
            'body' => 'Updated body',
            'to' => ['new@example.com'],
        ];

        $thread = Draft::save($data, $this->user);

        $this->assertNotNull($thread);
        $this->assertEquals($existingDraft->id, $thread->id);
        $this->assertEquals('Updated body', $thread->body);
    }

    public function test_save_updates_existing_draft_for_conversation(): void
    {
        $existingDraft = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
            'created_by_user_id' => $this->user->id,
            'state' => Thread::STATE_DRAFT,
            'body' => 'Original body',
        ]);

        $data = [
            'conversation_id' => $this->conversation->id,
            'body' => 'Updated body for existing draft',
            'to' => ['customer@example.com'],
        ];

        $thread = Draft::save($data, $this->user);

        $this->assertNotNull($thread);
        $this->assertEquals($existingDraft->id, $thread->id);
        $this->assertEquals('Updated body for existing draft', $thread->body);
    }

    public function test_save_returns_null_for_nonexistent_conversation(): void
    {
        $data = [
            'conversation_id' => 999999,
            'body' => 'Draft for nonexistent conversation',
        ];

        $thread = Draft::save($data, $this->user);

        $this->assertNull($thread);
    }

    public function test_save_returns_null_without_conversation_or_thread_id(): void
    {
        $data = [
            'body' => 'Draft without conversation',
        ];

        $thread = Draft::save($data, $this->user);

        $this->assertNull($thread);
    }

    public function test_save_does_not_update_another_users_draft(): void
    {
        $otherUser = User::factory()->create();

        $existingDraft = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
            'created_by_user_id' => $otherUser->id,
            'state' => Thread::STATE_DRAFT,
            'body' => 'Other users draft',
        ]);

        $data = [
            'thread_id' => $existingDraft->id,
            'body' => 'Attempted update',
        ];

        $thread = Draft::save($data, $this->user);

        // Should return null since this user doesn't own the draft
        $this->assertNull($thread);

        // Original draft should be unchanged
        $existingDraft->refresh();
        $this->assertEquals('Other users draft', $existingDraft->body);
    }

    public function test_discard_deletes_users_draft(): void
    {
        $draft = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
            'created_by_user_id' => $this->user->id,
            'state' => Thread::STATE_DRAFT,
        ]);

        $result = Draft::discard($draft->id, $this->user);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('threads', ['id' => $draft->id]);
    }

    public function test_discard_does_not_delete_another_users_draft(): void
    {
        $otherUser = User::factory()->create();

        $draft = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
            'created_by_user_id' => $otherUser->id,
            'state' => Thread::STATE_DRAFT,
        ]);

        $result = Draft::discard($draft->id, $this->user);

        $this->assertFalse($result);
        $this->assertDatabaseHas('threads', ['id' => $draft->id]);
    }

    public function test_discard_does_not_delete_non_draft_thread(): void
    {
        $thread = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
            'created_by_user_id' => $this->user->id,
            'state' => Thread::STATE_PUBLISHED,
        ]);

        $result = Draft::discard($thread->id, $this->user);

        $this->assertFalse($result);
        $this->assertDatabaseHas('threads', ['id' => $thread->id]);
    }

    public function test_discard_returns_false_for_nonexistent_thread(): void
    {
        $result = Draft::discard(999999, $this->user);

        $this->assertFalse($result);
    }

    public function test_save_includes_cc_and_bcc(): void
    {
        $data = [
            'conversation_id' => $this->conversation->id,
            'body' => 'Draft with recipients',
            'to' => ['to@example.com'],
            'cc' => ['cc@example.com'],
            'bcc' => ['bcc@example.com'],
        ];

        $thread = Draft::save($data, $this->user);

        $this->assertNotNull($thread);
        $this->assertEquals(['to@example.com'], $thread->to);
        $this->assertEquals(['cc@example.com'], $thread->cc);
        $this->assertEquals(['bcc@example.com'], $thread->bcc);
    }

    public function test_draft_save_requires_valid_conversation_authorization_context(): void
    {
        // Validation boundary: Draft::save must be linked to a valid
        // conversation — saving without a conversation_id violates the
        // authorization context requirement (drafts belong to a conversation).
        $data = [
            'conversation_id' => $this->conversation->id,
            'body' => 'Authorization context draft',
            'to' => ['requiredrecipient@example.com'],
        ];

        $thread = Draft::save($data, $this->user);

        $this->assertEquals(
            $this->conversation->id,
            $thread->conversation_id,
            'Draft authorization context: conversation_id must match the originating conversation'
        );
    }
}
