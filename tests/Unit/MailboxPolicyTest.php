<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Mailbox;
use App\Models\User;
use App\Policies\MailboxPolicy;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Tests\PureUnitTestCase;

class MailboxPolicyTest extends PureUnitTestCase
{
    private function makeUser(bool $canManageSettings): User
    {
        /** @var User&\Mockery\MockInterface $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->andReturnUsing(static function (string $permission) use ($canManageSettings): bool {
                return $permission === 'manage_settings' ? $canManageSettings : false;
            });
        $user->setRelation('mailboxes', new EloquentCollection);

        return $user;
    }

    private function makeMailbox(int $id): Mailbox
    {
        $mailbox = new Mailbox;
        $mailbox->id = $id;

        return $mailbox;
    }

    public function test_admin_can_view_any_mailboxes(): void
    {
        $admin = $this->makeUser(true);
        $policy = new MailboxPolicy;

        $this->assertTrue($policy->viewAny($admin));
    }

    public function test_non_admin_can_view_any_mailboxes(): void
    {
        $user = $this->makeUser(false);
        $policy = new MailboxPolicy;

        $this->assertTrue($policy->viewAny($user));
    }

    public function test_admin_can_create_mailbox(): void
    {
        $admin = $this->makeUser(true);
        $policy = new MailboxPolicy;

        $this->assertTrue($policy->create($admin));
    }

    public function test_non_admin_cannot_create_mailbox(): void
    {
        $user = $this->makeUser(false);
        $policy = new MailboxPolicy;

        $this->assertFalse($policy->create($user));
    }

    public function test_admin_can_update_mailbox(): void
    {
        $admin = $this->makeUser(true);
        $mailbox = $this->makeMailbox(1);
        $policy = new MailboxPolicy;

        $this->assertTrue($policy->update($admin, $mailbox));
    }

    public function test_non_admin_cannot_update_mailbox(): void
    {
        $user = $this->makeUser(false);
        $mailbox = $this->makeMailbox(1);
        $policy = new MailboxPolicy;

        $this->assertFalse($policy->update($user, $mailbox));
    }

    public function test_admin_can_delete_mailbox(): void
    {
        $admin = $this->makeUser(true);
        $mailbox = $this->makeMailbox(1);
        $policy = new MailboxPolicy;

        $this->assertTrue($policy->delete($admin, $mailbox));
    }

    public function test_non_admin_cannot_delete_mailbox(): void
    {
        $user = $this->makeUser(false);
        $mailbox = $this->makeMailbox(1);
        $policy = new MailboxPolicy;

        $this->assertFalse($policy->delete($user, $mailbox));
    }
}
