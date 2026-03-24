<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\PureUnitTestCase;

class UserAdminTest extends PureUnitTestCase
{
    public function test_is_admin_returns_true_for_unsaved_legacy_admin_role(): void
    {
        $user = new User(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($user->isAdmin());
    }

    public function test_is_admin_returns_false_for_unsaved_non_admin_role(): void
    {
        $user = new User(['role' => User::ROLE_USER]);

        $this->assertFalse($user->isAdmin());
    }

    public function test_clear_rbac_cache_resets_cached_is_admin_result_for_unsaved_users(): void
    {
        $user = new User(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($user->isAdmin());

        $user->role = User::ROLE_USER;

        $this->assertTrue($user->isAdmin());

        $user->clearRbacCache();

        $this->assertFalse($user->isAdmin());
    }
}
