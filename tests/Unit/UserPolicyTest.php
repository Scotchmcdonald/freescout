<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Policies\UserPolicy;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    protected UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy;
    }

    public function test_admin_can_view_any_users(): void
    {
        $admin = new User;
        $admin->role = User::ROLE_ADMIN; // 2

        $this->assertTrue($this->policy->viewAny($admin));
    }

    public function test_non_admin_cannot_view_any_users(): void
    {
        $user = new User;
        $user->role = User::ROLE_USER; // 1

        $this->assertFalse($this->policy->viewAny($user));
    }

    public function test_admin_can_create_user(): void
    {
        $admin = new User;
        $admin->role = User::ROLE_ADMIN; // 2

        $this->assertTrue($this->policy->create($admin));
    }

    public function test_non_admin_cannot_create_user(): void
    {
        $user = new User;
        $user->role = User::ROLE_USER; // 1

        $this->assertFalse($this->policy->create($user));
    }

    public function test_admin_can_update_user(): void
    {
        $admin = new User;
        $admin->id = 1;
        $admin->role = User::ROLE_ADMIN; // 2

        $targetUser = new User;
        $targetUser->id = 2;
        $targetUser->role = User::ROLE_USER; // 1

        $this->assertTrue($this->policy->update($admin, $targetUser));
    }

    public function test_admin_can_delete_other_user(): void
    {
        $admin = new User;
        $admin->id = 1;
        $admin->role = User::ROLE_ADMIN; // 2

        $targetUser = new User;
        $targetUser->id = 2;
        $targetUser->role = User::ROLE_USER; // 1

        $this->assertTrue($this->policy->delete($admin, $targetUser));
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = new User;
        $admin->id = 1;
        $admin->role = User::ROLE_ADMIN; // 2

        $this->assertFalse($this->policy->delete($admin, $admin));
    }

    public function test_non_admin_cannot_delete_user(): void
    {
        $user = new User;
        $user->id = 1;
        $user->role = User::ROLE_USER; // 1

        $targetUser = new User;
        $targetUser->id = 2;
        $targetUser->role = User::ROLE_USER; // 1

        $this->assertFalse($this->policy->delete($user, $targetUser));
    }

    public function test_user_can_update_themselves(): void
    {
        $user = new User;
        $user->id = 1;
        $user->role = User::ROLE_USER;

        // User can update their own profile
        $this->assertTrue($this->policy->update($user, $user));
    }

    public function test_user_cannot_update_other_users(): void
    {
        $user = new User;
        $user->id = 1;
        $user->role = User::ROLE_USER;

        $otherUser = new User;
        $otherUser->id = 2;
        $otherUser->role = User::ROLE_USER;

        // Regular user cannot update other users
        $this->assertFalse($this->policy->update($user, $otherUser));
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $user = new User;
        $user->id = 1;
        $user->role = User::ROLE_USER;

        // User cannot delete their own account
        $this->assertFalse($this->policy->delete($user, $user));
    }

    public function test_admin_can_update_themselves(): void
    {
        $admin = new User;
        $admin->id = 1;
        $admin->role = User::ROLE_ADMIN;

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
        $targetUser = new User;
        $targetUser->id = 1;
        $targetUser->role = User::ROLE_USER;

        $this->assertFalse($this->policy->view(null, $targetUser));
    }

    public function test_null_user_cannot_update_user(): void
    {
        $targetUser = new User;
        $targetUser->id = 1;
        $targetUser->role = User::ROLE_USER;

        $this->assertFalse($this->policy->update(null, $targetUser));
    }

    public function test_null_user_cannot_delete_user(): void
    {
        $targetUser = new User;
        $targetUser->id = 1;
        $targetUser->role = User::ROLE_USER;

        $this->assertFalse($this->policy->delete(null, $targetUser));
    }

    public function test_admin_can_view_user(): void
    {
        $admin = new User;
        $admin->id = 1;
        $admin->role = User::ROLE_ADMIN;

        $targetUser = new User;
        $targetUser->id = 2;
        $targetUser->role = User::ROLE_USER;

        $this->assertTrue($this->policy->view($admin, $targetUser));
    }

    public function test_user_can_view_themselves(): void
    {
        $user = new User;
        $user->id = 1;
        $user->role = User::ROLE_USER;

        $this->assertTrue($this->policy->view($user, $user));
    }

    public function test_user_cannot_view_other_users(): void
    {
        $user = new User;
        $user->id = 1;
        $user->role = User::ROLE_USER;

        $otherUser = new User;
        $otherUser->id = 2;
        $otherUser->role = User::ROLE_USER;

        $this->assertFalse($this->policy->view($user, $otherUser));
    }
}
