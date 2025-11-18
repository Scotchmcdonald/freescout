<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Mailbox;
use App\Models\User;
use App\Policies\MailboxPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailboxPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function authenticated_user_can_view_any_mailboxes()
    {
        $user = User::factory()->create();
        $policy = new MailboxPolicy;

        $this->assertTrue($policy->viewAny($user));
    }

    public function unauthenticated_user_cannot_view_any_mailboxes()
    {
        $policy = new MailboxPolicy;

        $this->assertFalse($policy->viewAny(null));
    }

    public function admin_can_view_any_mailbox()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();

        $policy = new MailboxPolicy;

        $this->assertTrue($policy->view($admin, $mailbox));
    }

    public function user_with_view_access_can_view_mailbox()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        $user->mailboxes()->attach($mailbox->id, ['access' => MailboxPolicy::ACCESS_VIEW]);

        $policy = new MailboxPolicy;

        $this->assertTrue($policy->view($user, $mailbox));
    }

    public function user_without_access_cannot_view_mailbox()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        $policy = new MailboxPolicy;

        $this->assertFalse($policy->view($user, $mailbox));
    }

    public function unauthenticated_user_cannot_view_mailbox()
    {
        $mailbox = Mailbox::factory()->create();
        $policy = new MailboxPolicy;

        $this->assertFalse($policy->view(null, $mailbox));
    }

    public function admin_can_create_mailbox()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $policy = new MailboxPolicy;

        $this->assertTrue($policy->create($admin));
    }

    public function non_admin_cannot_create_mailbox()
    {
        $user = User::factory()->create();
        $policy = new MailboxPolicy;

        $this->assertFalse($policy->create($user));
    }

    public function admin_can_update_any_mailbox()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();

        $policy = new MailboxPolicy;

        $this->assertTrue($policy->update($admin, $mailbox));
    }

    public function user_with_admin_access_can_update_mailbox()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        $user->mailboxes()->attach($mailbox->id, ['access' => MailboxPolicy::ACCESS_ADMIN]);

        $policy = new MailboxPolicy;

        $this->assertTrue($policy->update($user, $mailbox));
    }

    public function user_with_view_or_reply_access_cannot_update_mailbox()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        $user->mailboxes()->attach($mailbox->id, ['access' => MailboxPolicy::ACCESS_REPLY]);

        $policy = new MailboxPolicy;

        $this->assertFalse($policy->update($user, $mailbox));
    }

    public function admin_can_delete_mailbox()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();

        $policy = new MailboxPolicy;

        $this->assertTrue($policy->delete($admin, $mailbox));
    }

    public function non_admin_cannot_delete_mailbox()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        $user->mailboxes()->attach($mailbox->id, ['access' => MailboxPolicy::ACCESS_ADMIN]);

        $policy = new MailboxPolicy;

        $this->assertFalse($policy->delete($user, $mailbox));
    }

    public function user_with_reply_access_can_reply_to_conversations()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        $user->mailboxes()->attach($mailbox->id, ['access' => MailboxPolicy::ACCESS_REPLY]);

        $policy = new MailboxPolicy;

        $this->assertTrue($policy->reply($user, $mailbox));
    }

    public function user_with_view_access_cannot_reply_to_conversations()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        $user->mailboxes()->attach($mailbox->id, ['access' => MailboxPolicy::ACCESS_VIEW]);

        $policy = new MailboxPolicy;

        $this->assertFalse($policy->reply($user, $mailbox));
    }

    public function admin_can_administer_mailbox()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();

        $policy = new MailboxPolicy;

        $this->assertTrue($policy->admin($admin, $mailbox));
    }

    public function user_with_admin_access_can_administer_mailbox()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        $user->mailboxes()->attach($mailbox->id, ['access' => MailboxPolicy::ACCESS_ADMIN]);

        $policy = new MailboxPolicy;

        $this->assertTrue($policy->admin($user, $mailbox));
    }

    public function user_with_reply_access_cannot_administer_mailbox()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        $user->mailboxes()->attach($mailbox->id, ['access' => MailboxPolicy::ACCESS_REPLY]);

        $policy = new MailboxPolicy;

        $this->assertFalse($policy->admin($user, $mailbox));
    }

    public function access_level_hierarchy_is_respected()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $policy = new MailboxPolicy;

        // ACCESS_VIEW = 10
        $user->mailboxes()->attach($mailbox->id, ['access' => MailboxPolicy::ACCESS_VIEW]);
        $user->load('mailboxes');
        $this->assertTrue($policy->view($user, $mailbox));
        $this->assertFalse($policy->reply($user, $mailbox));
        $this->assertFalse($policy->update($user, $mailbox));

        // ACCESS_REPLY = 20
        $user->mailboxes()->detach($mailbox->id);
        $user->mailboxes()->attach($mailbox->id, ['access' => MailboxPolicy::ACCESS_REPLY]);
        $user->load('mailboxes');
        $this->assertTrue($policy->view($user, $mailbox));
        $this->assertTrue($policy->reply($user, $mailbox));
        $this->assertFalse($policy->update($user, $mailbox));

        // ACCESS_ADMIN = 30
        $user->mailboxes()->detach($mailbox->id);
        $user->mailboxes()->attach($mailbox->id, ['access' => MailboxPolicy::ACCESS_ADMIN]);
        $user->load('mailboxes');
        $this->assertTrue($policy->view($user, $mailbox));
        $this->assertTrue($policy->reply($user, $mailbox));
        $this->assertTrue($policy->update($user, $mailbox));
    }
}
