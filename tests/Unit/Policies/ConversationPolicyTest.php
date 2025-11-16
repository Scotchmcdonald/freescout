<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use App\Policies\ConversationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationPolicyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_view_any_conversation()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $conversation = Conversation::factory()->create();

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->view($admin, $conversation));
    }

    /** @test */
    public function user_with_mailbox_access_can_view_conversation()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $user->mailboxes()->attach($mailbox->id, ['access' => 10]);

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->view($user, $conversation));
    }

    /** @test */
    public function user_without_mailbox_access_cannot_view_conversation()
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();

        $policy = new ConversationPolicy;

        $this->assertFalse($policy->view($user, $conversation));
    }

    /** @test */
    public function admin_can_update_any_conversation()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $conversation = Conversation::factory()->create();

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->update($admin, $conversation));
    }

    /** @test */
    public function user_with_mailbox_access_can_update_conversation()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $user->mailboxes()->attach($mailbox->id, ['access' => 10]);

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->update($user, $conversation));
    }

    /** @test */
    public function user_without_mailbox_access_cannot_update_conversation()
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();

        $policy = new ConversationPolicy;

        $this->assertFalse($policy->update($user, $conversation));
    }

    /** @test */
    public function admin_can_delete_any_conversation()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $conversation = Conversation::factory()->create();

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->delete($admin, $conversation));
    }

    /** @test */
    public function user_with_mailbox_access_can_delete_conversation()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $user->mailboxes()->attach($mailbox->id, ['access' => 10]);

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->delete($user, $conversation));
    }

    /** @test */
    public function user_without_mailbox_access_cannot_delete_conversation()
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();

        $policy = new ConversationPolicy;

        $this->assertFalse($policy->delete($user, $conversation));
    }

    /** @test */
    public function user_can_move_conversations_if_they_have_multiple_mailbox_access()
    {
        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();

        $user->mailboxes()->attach($mailbox1->id, ['access' => 10]);
        $user->mailboxes()->attach($mailbox2->id, ['access' => 10]);

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->move($user));
    }

    /** @test */
    public function user_can_move_conversations_if_multiple_mailboxes_exist()
    {
        $user = User::factory()->create();
        Mailbox::factory()->count(2)->create();

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->move($user));
    }

    /** @test */
    public function user_cannot_move_conversations_if_only_one_mailbox()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        $user->mailboxes()->attach($mailbox->id, ['access' => 10]);

        $policy = new ConversationPolicy;

        $this->assertFalse($policy->move($user));
    }

    /** @test */
    public function view_cached_works_with_admin()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $conversation = Conversation::factory()->create();

        $policy = new ConversationPolicy;

        $this->assertTrue($policy->viewCached($admin, $conversation));
    }

    /** @test */
    public function view_cached_works_with_loaded_mailbox_users()
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
}
