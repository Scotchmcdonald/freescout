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
        $admin = new User();
        $admin->role = User::ROLE_ADMIN; // 2
        $policy = new UserPolicy();
        
        $this->assertTrue($policy->viewAny($admin));
    }

    public function test_non_admin_cannot_view_any_users(): void
    {
        $user = new User();
        $user->role = User::ROLE_USER; // 1
        $policy = new UserPolicy();
        
        $this->assertFalse($policy->viewAny($user));
    }

    public function test_admin_can_create_user(): void
    {
        $admin = new User();
        $admin->role = User::ROLE_ADMIN; // 2
        $policy = new UserPolicy();
        
        $this->assertTrue($policy->create($admin));
    }

    public function test_non_admin_cannot_create_user(): void
    {
        $user = new User();
        $user->role = User::ROLE_USER; // 1
        $policy = new UserPolicy();
        
        $this->assertFalse($policy->create($user));
    }

    public function test_admin_can_update_user(): void
    {
        $admin = new User();
        $admin->id = 1;
        $admin->role = User::ROLE_ADMIN; // 2
        
        $targetUser = new User();
        $targetUser->id = 2;
        $targetUser->role = User::ROLE_USER; // 1
        
        $policy = new UserPolicy();
        
        $this->assertTrue($policy->update($admin, $targetUser));
    }

    public function test_admin_can_delete_other_user(): void
    {
        $admin = new User();
        $admin->id = 1;
        $admin->role = User::ROLE_ADMIN; // 2
        
        $targetUser = new User();
        $targetUser->id = 2;
        $targetUser->role = User::ROLE_USER; // 1
        
        $policy = new UserPolicy();
        
        $this->assertTrue($policy->delete($admin, $targetUser));
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = new User();
        $admin->id = 1;
        $admin->role = User::ROLE_ADMIN; // 2
        
        $policy = new UserPolicy();
        
        $this->assertFalse($policy->delete($admin, $admin));
    }

    public function test_non_admin_cannot_delete_user(): void
    {
        $user = new User();
        $user->id = 1;
        $user->role = User::ROLE_USER; // 1
        
        $targetUser = new User();
        $targetUser->id = 2;
        $targetUser->role = User::ROLE_USER; // 1
        
        $policy = new UserPolicy();
        
        $this->assertFalse($policy->delete($user, $targetUser));
    }
}
