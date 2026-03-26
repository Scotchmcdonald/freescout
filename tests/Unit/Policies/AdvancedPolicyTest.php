<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Mailbox;
use App\Models\User;
use App\Policies\MailboxPolicy;
use App\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Tests\PureUnitTestCase;

class AdvancedPolicyTest extends PureUnitTestCase
{
    private int $nextUserId = 1000;

    private function makeUser(bool $canManageSettings = false, bool $canManageUsers = false): User
    {
        /** @var User&\Mockery\MockInterface $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = $this->nextUserId++;
        $user->setRelation('mailboxes', new EloquentCollection);

        $user->shouldReceive('hasPermission')
            ->andReturnUsing(static function (string $permission) use ($canManageSettings, $canManageUsers): bool {
                return match ($permission) {
                    'manage_settings' => $canManageSettings,
                    'manage_users' => $canManageUsers,
                    default => false,
                };
            });

        return $user;
    }

    private function attachMailboxAccess(User $user, int $mailboxId, int $access = MailboxPolicy::ACCESS_VIEW): void
    {
        $mailbox = new Mailbox;
        $mailbox->id = $mailboxId;
        $mailbox->pivot = (object) ['access' => $access];

        $mailboxes = $user->mailboxes;
        $mailboxes->push($mailbox);
        $user->setRelation('mailboxes', $mailboxes);
    }

    public function test_admin_can_manage_all_mailboxes(): void
    {
        $admin = $this->makeUser(canManageSettings: true);
        $mailbox = new Mailbox;
        $mailbox->id = 10;
        $policy = new MailboxPolicy;

        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->view($admin, $mailbox));
        $this->assertTrue($policy->update($admin, $mailbox));
        $this->assertTrue($policy->delete($admin, $mailbox));
    }

    public function test_user_can_only_view_assigned_mailboxes(): void
    {
        $user = $this->makeUser();
        $assignedMailbox = new Mailbox;
        $assignedMailbox->id = 20;
        $unassignedMailbox = new Mailbox;
        $unassignedMailbox->id = 21;
        $this->attachMailboxAccess($user, 20, MailboxPolicy::ACCESS_VIEW);

        $policy = new MailboxPolicy;

        $this->assertTrue($policy->view($user, $assignedMailbox));
        $this->assertFalse($policy->view($user, $unassignedMailbox));
    }

    public function test_user_cannot_delete_mailboxes(): void
    {
        $user = $this->makeUser();
        $mailbox = new Mailbox;
        $mailbox->id = 30;
        $this->attachMailboxAccess($user, 30, MailboxPolicy::ACCESS_ADMIN);

        $policy = new MailboxPolicy;

        $this->assertFalse($policy->delete($user, $mailbox));
    }

    public function test_admin_can_manage_all_users(): void
    {
        $admin = $this->makeUser(canManageUsers: true);
        $targetUser = $this->makeUser();
        $policy = new UserPolicy;

        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->view($admin, $targetUser));
        $this->assertTrue($policy->update($admin, $targetUser));
        $this->assertTrue($policy->delete($admin, $targetUser));
    }

    public function test_user_cannot_manage_other_users(): void
    {
        $user = $this->makeUser();
        $otherUser = $this->makeUser();
        $policy = new UserPolicy;

        $this->assertFalse($policy->viewAny($user));
        $this->assertFalse($policy->update($user, $otherUser));
        $this->assertFalse($policy->delete($user, $otherUser));
    }

    public function test_user_can_view_own_profile(): void
    {
        $user = $this->makeUser();
        $policy = new UserPolicy;

        $this->assertTrue($policy->view($user, $user));
    }

    public function test_guest_cannot_access_any_resources(): void
    {
        $mailbox = new Mailbox;
        $mailbox->id = 40;
        $user = $this->makeUser();

        $mailboxPolicy = new MailboxPolicy;
        $userPolicy = new UserPolicy;

        $this->assertFalse($mailboxPolicy->viewAny(null));
        $this->assertFalse($mailboxPolicy->view(null, $mailbox));
        $this->assertFalse($userPolicy->viewAny(null));
        $this->assertFalse($userPolicy->view(null, $user));
    }

    public function test_authorization_boundary_non_admin_cannot_delete_other_user(): void
    {
        // Authorization validation: the UserPolicy must deny deletion of other
        // accounts when the acting user has no admin role
        $actor  = $this->makeUser();
        $target = $this->makeUser();

        $policy = new UserPolicy;

        // Non-admin acting on another account must be denied
        $this->assertFalse($policy->delete($actor, $target),
            'Authorization must deny non-admin deletion of other user accounts'
        );
    }
}
