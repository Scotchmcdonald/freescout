<?php

declare(strict_types=1);

namespace Tests\Integration\Actions;

use App\Actions\BulkConversationsAction;
use App\DataTransferObjects\BulkConversationData;
use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkConversationsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_rejects_invalid_bulk_action(): void
    {
        $user = User::factory()->create();
        $action = new BulkConversationsAction;

        $result = $action->execute(new BulkConversationData([1], 'bulk_unknown'), $user);

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid bulk action', $result['message']);
        $this->assertSame(0, $result['count']);
    }

    public function test_reporter_cannot_bulk_close_conversations(): void
    {
        $mailbox = Mailbox::factory()->create();
        $reporter = User::factory()->create(['role' => User::ROLE_REPORTER]);
        $reporter->mailboxes()->attach($mailbox->id, ['access' => 10]);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $action = new BulkConversationsAction;
        $result = $action->execute(new BulkConversationData(
            [$conversation->id],
            'bulk_change_status',
            ConversationStatus::Closed,
        ), $reporter->load('mailboxes'));

        $this->assertFalse($result['success']);
        $this->assertSame('Reporters cannot close tickets', $result['message']);
        $this->assertSame(0, $result['count']);
        $this->assertSame(Conversation::STATUS_ACTIVE, $conversation->fresh()->status);
    }

    public function test_execute_ignores_inaccessible_conversations_when_bulk_deleting(): void
    {
        $accessibleMailbox = Mailbox::factory()->create();
        $otherMailbox = Mailbox::factory()->create();
        $user = User::factory()->create();
        $user->mailboxes()->attach($accessibleMailbox->id, ['access' => 30]);
        $accessibleConversation = Conversation::factory()->create(['mailbox_id' => $accessibleMailbox->id]);
        $hiddenConversation = Conversation::factory()->create(['mailbox_id' => $otherMailbox->id]);

        $action = new BulkConversationsAction;
        $result = $action->execute(new BulkConversationData(
            [$accessibleConversation->id, $hiddenConversation->id],
            'bulk_delete',
        ), $user->load('mailboxes'));

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['count']);
        $this->assertSame(Conversation::STATE_DELETED, $accessibleConversation->fresh()->state);
        $this->assertNotSame(Conversation::STATE_DELETED, $hiddenConversation->fresh()->state);
    }

    public function test_bulk_move_requires_existing_target_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $action = new BulkConversationsAction;
        $result = $action->execute(new BulkConversationData(
            [$conversation->id],
            'bulk_move',
            mailboxId: 999999,
        ), $user);

        $this->assertFalse($result['success']);
        $this->assertSame('Target mailbox not found', $result['message']);
        $this->assertSame($mailbox->id, $conversation->fresh()->mailbox_id);
    }
}
