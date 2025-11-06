<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Mailbox;
use App\Models\User;
use App\Policies\MailboxPolicy;
use Tests\TestCase;

class MailboxPolicyTest extends TestCase
{
    public function test_admin_can_view_any_mailboxes(): void
    {
        $admin = new User(['role' => User::ROLE_ADMIN]);
        $policy = new MailboxPolicy();
        
        $this->assertTrue($policy->viewAny($admin));
    }

    public function test_non_admin_can_view_any_mailboxes(): void
    {
        $user = new User(['role' => User::ROLE_USER]);
        $policy = new MailboxPolicy();
        
        $this->assertTrue($policy->viewAny($user));
    }

    public function test_admin_can_create_mailbox(): void
    {
        $admin = new User(['role' => User::ROLE_ADMIN]);
        $policy = new MailboxPolicy();
        
        $this->assertTrue($policy->create($admin));
    }

    public function test_non_admin_cannot_create_mailbox(): void
    {
        $user = new User(['role' => User::ROLE_USER]);
        $policy = new MailboxPolicy();
        
        $this->assertFalse($policy->create($user));
    }

    public function test_admin_can_update_mailbox(): void
    {
        $admin = new User(['role' => User::ROLE_ADMIN]);
        $mailbox = new Mailbox(['id' => 1]);
        $policy = new MailboxPolicy();
        
        $this->assertTrue($policy->update($admin, $mailbox));
    }

    public function test_non_admin_cannot_update_mailbox(): void
    {
        $user = new User(['role' => User::ROLE_USER]);
        $mailbox = new Mailbox(['id' => 1]);
        $policy = new MailboxPolicy();
        
        $this->assertFalse($policy->update($user, $mailbox));
    }

    public function test_admin_can_delete_mailbox(): void
    {
        $admin = new User(['role' => User::ROLE_ADMIN]);
        $mailbox = new Mailbox(['id' => 1]);
        $policy = new MailboxPolicy();
        
        $this->assertTrue($policy->delete($admin, $mailbox));
    }

    public function test_non_admin_cannot_delete_mailbox(): void
    {
        $user = new User(['role' => User::ROLE_USER]);
        $mailbox = new Mailbox(['id' => 1]);
        $policy = new MailboxPolicy();
        
        $this->assertFalse($policy->delete($user, $mailbox));
    }
}
