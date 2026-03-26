<?php

declare(strict_types=1);

namespace Tests\Integration\Policies;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use App\Policies\ConversationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_any_conversation()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $conversation = Conversation::factory()->create();

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->view($admin, $conversation));
    }

    public function test_user_with_mailbox_access_can_view_conversation()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $user->mailboxes()->attach($mailbox->id, ['access' => 10]);

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->view($user, $conversation));
    }

    public function test_user_without_mailbox_access_cannot_view_conversation()
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();

        $policy = new ConversationPolicy;

        $this->assertFalse($policy->view($user, $conversation));
    }

    public function test_admin_can_update_any_conversation()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $conversation = Conversation::factory()->create();

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->update($admin, $conversation));
    }

    public function test_user_with_mailbox_access_can_update_conversation()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $user->mailboxes()->attach($mailbox->id, ['access' => 10]);

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->update($user, $conversation));
    }

    public function test_user_without_mailbox_access_cannot_update_conversation()
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();

        $policy = new ConversationPolicy;

        $this->assertFalse($policy->update($user, $conversation));
    }

    public function test_admin_can_delete_any_conversation()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $conversation = Conversation::factory()->create();

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->delete($admin, $conversation));
    }

    public function test_user_with_mailbox_access_can_delete_conversation()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $user->mailboxes()->attach($mailbox->id, ['access' => 10]);

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->delete($user, $conversation));
    }

    public function test_user_without_mailbox_access_cannot_delete_conversation()
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();

        $policy = new ConversationPolicy;

        $this->assertFalse($policy->delete($user, $conversation));
    }

    public function test_user_can_move_conversations_if_they_have_multiple_mailbox_access()
    {
        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();

        $user->mailboxes()->attach($mailbox1->id, ['access' => 10]);
        $user->mailboxes()->attach($mailbox2->id, ['access' => 10]);

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->move($user));
    }

    public function test_user_can_move_conversations_if_multiple_mailboxes_exist()
    {
        $user = User::factory()->create();
        Mailbox::factory()->count(2)->create();

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->move($user));
    }

    public function test_user_cannot_move_conversations_if_only_one_mailbox()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        $user->mailboxes()->attach($mailbox->id, ['access' => 10]);

        $policy = new ConversationPolicy;

        $this->assertFalse($policy->move($user));
    }

    public function test_view_cached_works_with_admin()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $conversation = Conversation::factory()->create();

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->viewCached($admin, $conversation));
    }

    public function test_view_cached_works_with_loaded_mailbox_users()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $user->mailboxes()->attach($mailbox->id, ['access' => 10]);

        // Load the mailbox with users relationship
        $conversation->load('mailbox.users');

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->viewCached($user, $conversation));
    }

    public function test_user_without_mailbox_access_is_denied_view_authorization(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        // User has no mailbox attachment — authorization must be denied
        $policy = new ConversationPolicy;

        $this->assertFalse($policy->view($user, $conversation));
    }

    public function test_delete_authorization_denied_for_user_without_mailbox_access(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        // User has no access to the mailbox — delete authorization must fail
        $policy = new ConversationPolicy;

        $this->assertFalse($policy->delete($user, $conversation));
    }
}
