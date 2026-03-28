<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\ClientUserPolicy;
use Mockery;
use Tests\PureUnitTestCase;

class ClientUserPolicyTest extends PureUnitTestCase
{
    private function makeUser(
        int $id,
        array $permissions = [],
        bool $isActive = true,
        bool $isAdmin = false
    ): User {
        /** @var User&\Mockery\MockInterface $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = $id;

        $user->shouldReceive('hasPermission')
            ->andReturnUsing(static fn (string $permission): bool => in_array($permission, $permissions, true));
        $user->shouldReceive('isActive')->andReturn($isActive);
        $user->shouldReceive('isAdmin')->andReturn($isAdmin);

        return $user;
    }

    public function test_staff_with_view_crm_can_view_any_and_target_users(): void
    {
        $policy = new ClientUserPolicy;
        $staff = $this->makeUser(1, permissions: ['view_crm']);
        $target = $this->makeUser(2);

        $this->assertTrue($policy->viewAny($staff));
        $this->assertTrue($policy->view($staff, $target));
        $this->assertFalse($policy->create($staff));
    }

    public function test_staff_with_manage_crm_can_create_update_and_delete(): void
    {
        $policy = new ClientUserPolicy;
        $staff = $this->makeUser(1, permissions: ['manage_crm']);
        $target = $this->makeUser(2, isActive: true);

        $this->assertFalse($policy->viewAny($staff));
        $this->assertTrue($policy->create($staff));
        $this->assertTrue($policy->update($staff, $target));
        $this->assertTrue($policy->delete($staff, $target));
    }

    public function test_regular_external_user_can_only_view_and_update_self_when_active(): void
    {
        $policy = new ClientUserPolicy;
        $user = $this->makeUser(10, isActive: true);
        $otherUser = $this->makeUser(11, isActive: true);

        $this->assertTrue($policy->view($user, $user));
        $this->assertFalse($policy->view($user, $otherUser));
        $this->assertTrue($policy->update($user, $user));
        $this->assertFalse($policy->update($user, $otherUser));
        $this->assertFalse($policy->delete($user, $otherUser));
    }

    public function test_inactive_external_user_cannot_update_self(): void
    {
        $policy = new ClientUserPolicy;
        $user = $this->makeUser(10, isActive: false);

        $this->assertFalse($policy->update($user, $user));
    }

    public function test_toggle_active_requires_approve_users_permission(): void
    {
        $policy = new ClientUserPolicy;
        $approver = $this->makeUser(1, permissions: ['approve_users']);
        $nonApprover = $this->makeUser(2);
        $target = $this->makeUser(3, isActive: true);

        $this->assertTrue($policy->toggleActive($approver, $target));
        $this->assertFalse($policy->toggleActive($nonApprover, $target));
    }

    public function test_impersonation_requires_admin_and_active_target(): void
    {
        $policy = new ClientUserPolicy;
        $admin = $this->makeUser(1, isAdmin: true);
        $inactiveTarget = $this->makeUser(2, isActive: false);
        $activeTarget = $this->makeUser(3, isActive: true);
        $staff = $this->makeUser(4, isAdmin: false);

        $this->assertTrue($policy->impersonate($admin, $activeTarget));
        $this->assertFalse($policy->impersonate($admin, $inactiveTarget));
        $this->assertFalse($policy->impersonate($staff, $activeTarget));
    }
}
