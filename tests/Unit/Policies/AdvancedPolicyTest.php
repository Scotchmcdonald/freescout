<?php

namespace Tests\Unit\Policies;

use App\Models\Mailbox;
use App\Models\User;
use App\Policies\MailboxPolicy;
use App\Policies\UserPolicy;
use Tests\UnitTestCase;

class AdvancedPolicyTest extends UnitTestCase
{

    public function test_admin_can_manage_all_mailboxes()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $policy = new MailboxPolicy;

        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->view($admin, $mailbox));
        $this->assertTrue($policy->update($admin, $mailbox));
        $this->assertTrue($policy->delete($admin, $mailbox));
    }

    public function test_user_can_only_view_assigned_mailboxes()
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $assignedMailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($assignedMailbox->id);

        $unassignedMailbox = Mailbox::factory()->create();

        $policy = new MailboxPolicy;

        $this->assertTrue($policy->view($user, $assignedMailbox));
        $this->assertFalse($policy->view($user, $unassignedMailbox));
    }

    public function test_user_cannot_delete_mailboxes()
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox->id);

        $policy = new MailboxPolicy;

        $this->assertFalse($policy->delete($user, $mailbox));
    }

    public function test_admin_can_manage_all_users()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $targetUser = User::factory()->create();
        $policy = new UserPolicy;

        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->view($admin, $targetUser));
        $this->assertTrue($policy->update($admin, $targetUser));
        $this->assertTrue($policy->delete($admin, $targetUser));
    }

    public function test_user_cannot_manage_other_users()
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $otherUser = User::factory()->create();
        $policy = new UserPolicy;

        $this->assertFalse($policy->viewAny($user));
        $this->assertFalse($policy->update($user, $otherUser));
        $this->assertFalse($policy->delete($user, $otherUser));
    }

    public function test_user_can_view_own_profile()
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $policy = new UserPolicy;

        $this->assertTrue($policy->view($user, $user));
    }

    public function test_guest_cannot_access_any_resources()
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();

        $mailboxPolicy = new MailboxPolicy;
        $userPolicy = new UserPolicy;

        $this->assertFalse($mailboxPolicy->viewAny(null));
        $this->assertFalse($mailboxPolicy->view(null, $mailbox));
        $this->assertFalse($userPolicy->viewAny(null));
        $this->assertFalse($userPolicy->view(null, $user));
    }
}
