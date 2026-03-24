<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Tests\PureUnitTestCase;

final class TestUserHelperModel extends User
{
    public bool $adminStub = false;

    public bool $hasClientRole = false;

    protected function casts(): array
    {
        return [];
    }

    public function isAdmin(): bool
    {
        return $this->adminStub;
    }

    public function hasAnyRole(string|array $roles): bool
    {
        return $this->hasClientRole;
    }
}

class UserHelperMethodsTest extends PureUnitTestCase
{
    private ?Container $previousContainer = null;

    private mixed $previousFacadeApplication = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();

        $app = new Application(getcwd());
        $app->instance('config', new Repository([
            'app' => [
                'admin_email' => 'admin@example.com',
                'user_permissions' => null,
            ],
        ]));

        Container::setInstance($app);
        Facade::setFacadeApplication($app);
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    private function user(array $attrs = []): TestUserHelperModel
    {
        $user = new TestUserHelperModel;
        foreach ($attrs as $key => $value) {
            $user->{$key} = $value;
        }

        return $user;
    }

    public function test_has_verified_email_allows_deployment_admin_bypass_and_regular_verification(): void
    {
        $admin = $this->user([
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => null,
        ]);

        $verifiedUser = $this->user([
            'email' => 'user@example.com',
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $unverifiedUser = $this->user([
            'email' => 'user@example.com',
            'role' => User::ROLE_USER,
            'email_verified_at' => null,
        ]);

        $this->assertTrue($admin->hasVerifiedEmail());
        $this->assertTrue($verifiedUser->hasVerifiedEmail());
        $this->assertFalse($unverifiedUser->hasVerifiedEmail());
    }

    public function test_role_and_type_helpers_cover_internal_client_finance_reporter_active_and_automaton_states(): void
    {
        $user = $this->user([
            'role' => User::ROLE_FINANCE,
            'type' => User::TYPE_INTERNAL,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->assertTrue($user->isInternalStaff());
        $this->assertFalse($user->isClient());
        $this->assertFalse($user->isAutomaton());
        $this->assertTrue($user->isFinance());
        $this->assertFalse($user->isReporter());
        $this->assertTrue($user->isActive());

        $user->type = User::TYPE_AUTOMATON;
        $user->role = User::ROLE_REPORTER;
        $user->status = User::STATUS_INACTIVE;

        $this->assertTrue($user->isAutomaton());
        $this->assertTrue($user->isReporter());
        $this->assertFalse($user->isActive());
    }

    public function test_is_client_returns_true_for_client_roles_or_client_type(): void
    {
        $roleClient = $this->user(['type' => User::TYPE_INTERNAL]);
        $roleClient->hasClientRole = true;

        $typeClient = $this->user(['type' => User::TYPE_CLIENT]);

        $notClient = $this->user(['type' => User::TYPE_INTERNAL]);

        $this->assertTrue($roleClient->isClient());
        $this->assertTrue($typeClient->isClient());
        $this->assertFalse($notClient->isClient());
    }

    public function test_has_admin_access_depends_on_admin_or_internal_staff(): void
    {
        $admin = $this->user(['type' => User::TYPE_CLIENT]);
        $admin->adminStub = true;

        $internal = $this->user(['type' => User::TYPE_INTERNAL]);
        $external = $this->user(['type' => User::TYPE_CLIENT]);

        $this->assertTrue($admin->hasAdminAccess());
        $this->assertTrue($internal->hasAdminAccess());
        $this->assertFalse($external->hasAdminAccess());
    }

    public function test_name_helpers_photo_url_and_date_format_behave_as_expected(): void
    {
        $user = $this->user([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'Jane.Doe@example.com',
            'timezone' => 'UTC',
        ]);

        $this->assertSame('Jane Doe', $user->getFullName());
        $this->assertSame('Jane', $user->getFullName(true));
        $this->assertSame('Jane', $user->getFirstName());
        $this->assertStringContainsString(md5('jane.doe@example.com'), $user->getPhotoUrl());

        $formatted = User::dateFormat('2026-03-24 12:30:00', 'Y-m-d H:i', $user);
        $invalid = User::dateFormat('not-a-date', 'Y-m-d', $user);
        $empty = User::dateFormat(null, 'Y-m-d', $user);

        $this->assertSame('2026-03-24 12:30', $formatted);
        $this->assertSame('', $invalid);
        $this->assertSame('', $empty);
    }

    public function test_date_format_silently_ignores_invalid_timezone_and_still_formats_date(): void
    {
        $user = $this->user(['timezone' => 'Not/A/Valid/Timezone']);

        // The catch block swallows the DateTimeZone exception; date still formats in original UTC.
        $result = User::dateFormat('2026-03-24 12:30:00', 'Y-m-d H:i', $user);

        $this->assertSame('2026-03-24 12:30', $result);
    }
}
