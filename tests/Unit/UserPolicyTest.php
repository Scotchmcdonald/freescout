<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Policies\UserPolicy;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    public function test_admin_can_view_any_users(): void
    {
        $admin = new User;
        $admin->role = User::ROLE_ADMIN; // 2
        $policy = new UserPolicy;

        $this->assertTrue($policy->viewAny($admin));
    }

    public function test_non_admin_cannot_view_any_users(): void
    {
        $user = new User;
        $user->role = User::ROLE_USER; // 1
        $policy = new UserPolicy;

        $this->assertFalse($policy->viewAny($user));
    }

    public function test_admin_can_create_user(): void
    {
        $admin = new User;
        $admin->role = User::ROLE_ADMIN; // 2
        $policy = new UserPolicy;

        $this->assertTrue($policy->create($admin));
    }

    public function test_non_admin_cannot_create_user(): void
    {
        $user = new User;
        $user->role = User::ROLE_USER; // 1
        $policy = new UserPolicy;

        $this->assertFalse($policy->create($user));
    }

    public function test_admin_can_update_user(): void
    {
        $admin = new User;
        $admin->id = 1;
        $admin->role = User::ROLE_ADMIN; // 2

        $targetUser = new User;
        $targetUser->id = 2;
        $targetUser->role = User::ROLE_USER; // 1

        $policy = new UserPolicy;

        $this->assertTrue($policy->update($admin, $targetUser));
    }

    public function test_admin_can_delete_other_user(): void
    {
        $admin = new User;
        $admin->id = 1;
        $admin->role = User::ROLE_ADMIN; // 2

        $targetUser = new User;
        $targetUser->id = 2;
        $targetUser->role = User::ROLE_USER; // 1

        $policy = new UserPolicy;

        $this->assertTrue($policy->delete($admin, $targetUser));
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = new User;
        $admin->id = 1;
        $admin->role = User::ROLE_ADMIN; // 2

        $policy = new UserPolicy;

        $this->assertFalse($policy->delete($admin, $admin));
    }

    public function test_non_admin_cannot_delete_user(): void
    {
        $user = new User;
        $user->id = 1;
        $user->role = User::ROLE_USER; // 1

        $targetUser = new User;
        $targetUser->id = 2;
        $targetUser->role = User::ROLE_USER; // 1

        $policy = new UserPolicy;

        $this->assertFalse($policy->delete($user, $targetUser));
    }

    public function test_user_can_update_themselves(): void
    {
        $user = new User;
        $user->id = 1;
        $user->role = User::ROLE_USER;

        $policy = new UserPolicy;

        // User can update their own profile
        $this->assertTrue($policy->update($user, $user));
    }

    public function test_user_cannot_update_other_users(): void
    {
        $user = new User;
        $user->id = 1;
        $user->role = User::ROLE_USER;

        $otherUser = new User;
        $otherUser->id = 2;
        $otherUser->role = User::ROLE_USER;

        $policy = new UserPolicy;

        // Regular user cannot update other users
        $this->assertFalse($policy->update($user, $otherUser));
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $user = new User;
        $user->id = 1;
        $user->role = User::ROLE_USER;

        $policy = new UserPolicy;

        // User cannot delete their own account
        $this->assertFalse($policy->delete($user, $user));
    }

    public function test_admin_can_update_themselves(): void
    {
        $admin = new User;
        $admin->id = 1;
        $admin->role = User::ROLE_ADMIN;

        $policy = new UserPolicy;

        // Admin can update their own profile
        $this->assertTrue($policy->update($admin, $admin));
    }

    public function test_null_user_cannot_view_any_users(): void
    {
        $policy = new UserPolicy;

        $this->assertFalse($policy->viewAny(null));
    }

    public function test_null_user_cannot_create_user(): void
    {
        $policy = new UserPolicy;

        $this->assertFalse($policy->create(null));
    }

    public function test_null_user_cannot_view_user(): void
    {
        $targetUser = new User;
        $targetUser->id = 1;
        $targetUser->role = User::ROLE_USER;

        $policy = new UserPolicy;

        $this->assertFalse($policy->view(null, $targetUser));
    }

    public function test_null_user_cannot_update_user(): void
    {
        $targetUser = new User;
        $targetUser->id = 1;
        $targetUser->role = User::ROLE_USER;

        $policy = new UserPolicy;

        $this->assertFalse($policy->update(null, $targetUser));
    }

    public function test_null_user_cannot_delete_user(): void
    {
        $targetUser = new User;
        $targetUser->id = 1;
        $targetUser->role = User::ROLE_USER;

        $policy = new UserPolicy;

        $this->assertFalse($policy->delete(null, $targetUser));
    }

    public function test_admin_can_view_user(): void
    {
        $admin = new User;
        $admin->id = 1;
        $admin->role = User::ROLE_ADMIN;

        $targetUser = new User;
        $targetUser->id = 2;
        $targetUser->role = User::ROLE_USER;

        $policy = new UserPolicy;

        $this->assertTrue($policy->view($admin, $targetUser));
    }

    public function test_user_can_view_themselves(): void
    {
        $user = new User;
        $user->id = 1;
        $user->role = User::ROLE_USER;

        $policy = new UserPolicy;

        $this->assertTrue($policy->view($user, $user));
    }

    public function test_user_cannot_view_other_users(): void
    {
        $user = new User;
        $user->id = 1;
        $user->role = User::ROLE_USER;

        $otherUser = new User;
        $otherUser->id = 2;
        $otherUser->role = User::ROLE_USER;

        $policy = new UserPolicy;

        $this->assertFalse($policy->view($user, $otherUser));
    }
}
