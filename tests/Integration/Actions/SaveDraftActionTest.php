<?php

declare(strict_types=1);

namespace Tests\Integration\Actions;

use App\Actions\SaveDraftAction;
use App\DataTransferObjects\DraftData;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaveDraftActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_updates_existing_draft_without_overwriting_unspecified_recipients(): void
    {
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_email' => 'customer@example.com',
        ]);
        $user = User::factory()->create();
        $draft = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => Thread::TYPE_DRAFT,
            'state' => Thread::STATE_DRAFT,
            'body' => 'old body',
            'to' => 'existing-to@example.com',
            'cc' => 'existing-cc@example.com',
        ]);

        $action = new SaveDraftAction;
        $result = $action->execute(new DraftData(
            conversationId: $conversation->id,
            userId: $user->id,
            body: 'updated body',
            bcc: 'new-bcc@example.com',
        ), $conversation);

        $this->assertTrue($result['success']);
        $this->assertSame('Draft updated', $result['message']);
        $this->assertSame($draft->id, $result['draft']->id);
        $this->assertDatabaseHas('threads', [
            'id' => $draft->id,
            'body' => 'updated body',
            'to' => '"existing-to@example.com"',
            'cc' => '"existing-cc@example.com"',
            'bcc' => '"new-bcc@example.com"',
        ]);
    }

    public function test_execute_creates_new_draft_with_mailbox_and_customer_defaults(): void
    {
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_email' => 'customer@example.com',
        ]);
        $conversation->load('mailbox');
        $user = User::factory()->create();

        $action = new SaveDraftAction;
        $result = $action->execute(new DraftData(
            conversationId: $conversation->id,
            userId: $user->id,
            body: 'draft body'
        ), $conversation);

        $this->assertTrue($result['success']);
        $this->assertSame('Draft saved', $result['message']);
        $this->assertDatabaseHas('threads', [
            'id' => $result['draft']->id,
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => Thread::TYPE_DRAFT,
            'state' => Thread::STATE_DRAFT,
            'from' => 'support@example.com',
            'body' => 'draft body',
        ]);
        $this->assertSame('["customer@example.com"]', $result['draft']->to);
    }

    public function test_discard_reports_when_no_draft_exists(): void
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();

        $action = new SaveDraftAction;
        $result = $action->discard($conversation, $user->id);

        $this->assertTrue($result['success']);
        $this->assertSame('No draft found', $result['message']);
    }
}