<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use App\Policies\ConversationPolicy;
use Tests\UnitTestCase;

class ConversationPolicyTest extends UnitTestCase
{

    public function test_admin_can_view_conversation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $conversation = Conversation::factory()->create();
        $policy = new ConversationPolicy;

        $this->assertTrue($policy->view($admin, $conversation));
    }

    public function test_user_can_view_conversation_with_mailbox_access(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $user->mailboxes()->attach($mailbox->id);

        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $policy = new ConversationPolicy;

        $this->assertTrue($policy->view($user, $conversation));
    }

    public function test_user_cannot_view_conversation_without_mailbox_access(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $conversation = Conversation::factory()->create();
        $policy = new ConversationPolicy;

        $this->assertFalse($policy->view($user, $conversation));
    }

    public function test_admin_can_update_conversation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $conversation = Conversation::factory()->create();
        $policy = new ConversationPolicy;

        $this->assertTrue($policy->update($admin, $conversation));
    }

    public function test_user_can_update_conversation_with_mailbox_access(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $user->mailboxes()->attach($mailbox->id);

        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $policy = new ConversationPolicy;

        $this->assertTrue($policy->update($user, $conversation));
    }

    public function test_user_cannot_update_conversation_without_mailbox_access(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $conversation = Conversation::factory()->create();
        $policy = new ConversationPolicy;

        $this->assertFalse($policy->update($user, $conversation));
    }

    public function test_admin_can_delete_conversation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $conversation = Conversation::factory()->create();
        $policy = new ConversationPolicy;

        $this->assertTrue($policy->delete($admin, $conversation));
    }

    public function test_user_can_move_with_multiple_mailboxes(): void
    {
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $user->mailboxes()->attach([$mailbox1->id, $mailbox2->id]);

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->move($user));
    }

    public function test_user_cannot_move_with_single_mailbox(): void
    {
        // Clear any existing mailboxes
        Mailbox::query()->delete();

        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $user->mailboxes()->attach($mailbox->id);

        $policy = new ConversationPolicy;

        $this->assertFalse($policy->move($user));
    }
}
