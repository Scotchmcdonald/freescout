<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Application;
use Mockery;
use Tests\PureUnitTestCase;

final class TestUserPermissionModel extends User
{
    public bool $admin = false;

    /** @var \Illuminate\Support\Collection<int, int>|null */
    public $roleIds;

    /** @var array<string, object> */
    public array $permissionQueries = [];

    public function isAdmin(): bool
    {
        return $this->admin;
    }

    protected function getRbacRoleIds(): \Illuminate\Support\Collection
    {
        return $this->roleIds ?? collect();
    }

    protected function permissionQueryForName(string $permission): Builder
    {
        if (array_key_exists($permission, $this->permissionQueries)) {
            return $this->permissionQueries[$permission];
        }

        return parent::permissionQueryForName($permission);
    }
}

class UserPermissionLogicTest extends PureUnitTestCase
{
    private ?Container $previousContainer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();

        $app = new Application(getcwd());
        $app->instance('config', new Repository([
            'app' => [
                'user_permissions' => null,
            ],
        ]));

        Container::setInstance($app);
    }

    protected function tearDown(): void
    {
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    private function user(): TestUserPermissionModel
    {
        return new TestUserPermissionModel;
    }

    public function test_has_permission_returns_true_for_admin_without_further_checks(): void
    {
        $user = $this->user();
        $user->admin = true;

        $this->assertTrue($user->hasPermission('manage-mailbox'));
        $this->assertTrue($user->hasPermission(User::PERM_EDIT_USERS));
    }

    public function test_has_permission_returns_false_for_string_permissions_when_no_roles_are_available(): void
    {
        $user = $this->user();
        $user->role = 0;

        $this->assertFalse($user->hasPermission('manage-mailbox'));
    }

    public function test_has_permission_uses_global_legacy_permissions_when_present(): void
    {
        app('config')->set('app.user_permissions', base64_encode(json_encode([
            User::PERM_DELETE_CONVERSATIONS,
            User::PERM_EDIT_USERS,
        ], JSON_THROW_ON_ERROR)));

        $user = $this->user();

        $this->assertTrue($user->hasPermission(User::PERM_EDIT_USERS, false));
        $this->assertFalse($user->hasPermission(User::PERM_EDIT_TAGS, false));
    }

    public function test_has_permission_own_permissions_override_global_permissions(): void
    {
        app('config')->set('app.user_permissions', base64_encode(json_encode([
            User::PERM_EDIT_USERS,
        ], JSON_THROW_ON_ERROR)));

        $user = $this->user();
        $user->permissions = [
            User::PERM_EDIT_USERS => false,
            User::PERM_EDIT_TAGS => true,
        ];

        $this->assertFalse($user->hasPermission(User::PERM_EDIT_USERS));
        $this->assertTrue($user->hasPermission(User::PERM_EDIT_TAGS));
    }

    public function test_get_global_user_permissions_ignores_invalid_config_payloads(): void
    {
        app('config')->set('app.user_permissions', base64_encode('not-json'));

        $this->assertSame([], User::getGlobalUserPermissions());
    }

    public function test_has_permission_uses_role_fallback_and_cached_rbac_ids_for_string_permissions(): void
    {
        $legacyQuery = Mockery::mock(Builder::class);
        $legacyQuery->shouldReceive('whereHas')->once()->with(
            'roles',
            Mockery::on(function ($closure): bool {
                $query = Mockery::mock();
                $query->shouldReceive('whereIn')->once()->with('roles.id', Mockery::on(
                    fn ($roleIds): bool => $roleIds instanceof \Illuminate\Support\Collection
                        && $roleIds->values()->all() === [4]
                ))->andReturnSelf();
                $closure($query);

                return true;
            })
        )->andReturnSelf();
        $legacyQuery->shouldReceive('exists')->once()->andReturn(true);

        $rbacQuery = Mockery::mock(Builder::class);
        $rbacQuery->shouldReceive('whereHas')->once()->with(
            'roles',
            Mockery::on(function ($closure): bool {
                $query = Mockery::mock();
                $query->shouldReceive('whereIn')->once()->with('roles.id', Mockery::on(
                    fn ($roleIds): bool => $roleIds instanceof \Illuminate\Support\Collection
                        && $roleIds->values()->all() === [8, 9]
                ))->andReturnSelf();
                $closure($query);

                return true;
            })
        )->andReturnSelf();
        $rbacQuery->shouldReceive('exists')->once()->andReturn(false);

        $legacyFallbackUser = $this->user();
        $legacyFallbackUser->role = 4;
        $legacyFallbackUser->permissionQueries['manage-billing'] = $legacyQuery;

        $this->assertTrue($legacyFallbackUser->hasPermission('manage-billing'));

        $rbacUser = $this->user();
        $rbacUser->roleIds = collect([8, 9]);
        $rbacUser->permissionQueries['manage-mailbox'] = $rbacQuery;

        $this->assertFalse($rbacUser->hasPermission('manage-mailbox'));
    }

    public function test_has_permission_skips_own_permission_overrides_when_flag_is_disabled(): void
    {
        app('config')->set('app.user_permissions', base64_encode(json_encode([
            User::PERM_EDIT_USERS,
        ], JSON_THROW_ON_ERROR)));

        $user = $this->user();
        $user->permissions = [
            User::PERM_EDIT_USERS => false,
        ];

        $this->assertTrue($user->hasPermission(User::PERM_EDIT_USERS, false));
    }

    public function test_get_global_user_permissions_filters_non_integer_values(): void
    {
        app('config')->set('app.user_permissions', base64_encode(json_encode([
            User::PERM_EDIT_USERS,
            '10',
            true,
            null,
        ], JSON_THROW_ON_ERROR)));

        $this->assertSame([User::PERM_EDIT_USERS], User::getGlobalUserPermissions());
    }

    public function test_authorization_boundary_user_without_permission_is_unauthorized(): void
    {
        // Authorization boundary: a user without a granted permission must be
        // unauthorized — hasPermission must return false, not silently allow.
        $user = new User(['role' => User::ROLE_USER]);

        $this->assertFalse($user->hasPermission(User::PERM_EDIT_USERS),
            'Unauthorized user must not pass the permission authorization check'
        );
    }

    public function test_authorization_boundary_admin_bypasses_permission_checks(): void
    {
        // Authorization boundary: admin role bypasses per-permission authorization.
        // This is by design but must be explicitly validated as a boundary case.
        $user = new User(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($user->hasPermission(User::PERM_EDIT_USERS),
            'Admin authorization must grant all permission checks without explicit assignment'
        );
    }

    public function test_authorization_boundary_forbidden_permission_not_in_global_config(): void
    {
        // Authorization boundary: if a permission is not in the global config,
        // the user must be forbidden from that action (authorization returns false).
        app('config')->set('app.user_permissions', base64_encode(json_encode([], JSON_THROW_ON_ERROR)));

        $user = new User(['role' => User::ROLE_USER]);

        $this->assertFalse($user->hasPermission(User::PERM_EDIT_USERS),
            'User must be forbidden when permission is absent from the authorization config'
        );
    }
}
