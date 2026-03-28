<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Policies\UserPolicy;
use Mockery;
use Tests\PureUnitTestCase;

class UserPolicyTest extends PureUnitTestCase
{
    protected UserPolicy $policy;

    private function makeUser(int $id, bool $canManageUsers): User
    {
        /** @var User&\Mockery\MockInterface $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = $id;
        $user->shouldReceive('hasPermission')
            ->andReturnUsing(static function (string $permission) use ($canManageUsers): bool {
                return $permission === 'manage_users' ? $canManageUsers : false;
            });

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy;
    }

    public function test_admin_can_view_any_users(): void
    {
        $admin = $this->makeUser(1, true);

        $this->assertTrue($this->policy->viewAny($admin));
    }

    public function test_non_admin_cannot_view_any_users(): void
    {
        $user = $this->makeUser(1, false);

        $this->assertFalse($this->policy->viewAny($user));
    }

    public function test_admin_can_create_user(): void
    {
        $admin = $this->makeUser(1, true);

        $this->assertTrue($this->policy->create($admin));
    }

    public function test_non_admin_cannot_create_user(): void
    {
        $user = $this->makeUser(1, false);

        $this->assertFalse($this->policy->create($user));
    }

    public function test_admin_can_update_user(): void
    {
        $admin = $this->makeUser(1, true);
        $targetUser = $this->makeUser(2, false);

        $this->assertTrue($this->policy->update($admin, $targetUser));
    }

    public function test_admin_can_delete_other_user(): void
    {
        $admin = $this->makeUser(1, true);
        $targetUser = $this->makeUser(2, false);

        $this->assertTrue($this->policy->delete($admin, $targetUser));
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = $this->makeUser(1, true);

        $this->assertFalse($this->policy->delete($admin, $admin));
    }

    public function test_non_admin_cannot_delete_user(): void
    {
        $user = $this->makeUser(1, false);
        $targetUser = $this->makeUser(2, false);

        $this->assertFalse($this->policy->delete($user, $targetUser));
    }

    public function test_user_can_update_themselves(): void
    {
        $user = $this->makeUser(1, false);

        // User can update their own profile
        $this->assertTrue($this->policy->update($user, $user));
    }

    public function test_user_cannot_update_other_users(): void
    {
        $user = $this->makeUser(1, false);
        $otherUser = $this->makeUser(2, false);

        // Regular user cannot update other users
        $this->assertFalse($this->policy->update($user, $otherUser));
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $user = $this->makeUser(1, false);

        // User cannot delete their own account
        $this->assertFalse($this->policy->delete($user, $user));
    }

    public function test_admin_can_update_themselves(): void
    {
        $admin = $this->makeUser(1, true);

        // Admin can update their own profile
        $this->assertTrue($this->policy->update($admin, $admin));
    }

    public function test_null_user_cannot_view_any_users(): void
    {
        $this->assertFalse($this->policy->viewAny(null));
    }

    public function test_null_user_cannot_create_user(): void
    {
        $this->assertFalse($this->policy->create(null));
    }

    public function test_null_user_cannot_view_user(): void
    {
        $targetUser = $this->makeUser(1, false);

        $this->assertFalse($this->policy->view(null, $targetUser));
    }

    public function test_null_user_cannot_update_user(): void
    {
        $targetUser = $this->makeUser(1, false);

        $this->assertFalse($this->policy->update(null, $targetUser));
    }

    public function test_null_user_cannot_delete_user(): void
    {
        $targetUser = $this->makeUser(1, false);

        $this->assertFalse($this->policy->delete(null, $targetUser));
    }

    public function test_admin_can_view_user(): void
    {
        $admin = $this->makeUser(1, true);
        $targetUser = $this->makeUser(2, false);

        $this->assertTrue($this->policy->view($admin, $targetUser));
    }

    public function test_user_can_view_themselves(): void
    {
        $user = $this->makeUser(1, false);

        $this->assertTrue($this->policy->view($user, $user));
    }

    public function test_user_cannot_view_other_users(): void
    {
        $user = $this->makeUser(1, false);
        $otherUser = $this->makeUser(2, false);

        $this->assertFalse($this->policy->view($user, $otherUser));
    }

    public function test_authorization_boundary_unauthenticated_guest_is_denied_all_user_access(): void
    {
        // Authorization boundary: an unauthenticated (null) user must be denied
        // all policy actions — no implicit guest privileges are permitted.
        $target = $this->makeUser(1, false);

        $this->assertFalse(
            $this->policy->view(null, $target),
            'Authorization boundary: guest must not view any user'
        );
        $this->assertFalse(
            $this->policy->create(null),
            'Authorization boundary: guest must not create users'
        );
        $this->assertFalse(
            $this->policy->delete(null, $target),
            'Authorization boundary: guest must not delete users'
        );
    }
}
