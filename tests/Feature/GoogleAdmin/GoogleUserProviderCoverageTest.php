<?php

declare(strict_types=1);

use Modules\GoogleAdmin\Services\GoogleUserProvider;
use Modules\GoogleAdmin\Services\GoogleWorkspaceService;

it('returns source name for provider registry display', function () {
    $service = Mockery::mock(GoogleWorkspaceService::class);
    $provider = new GoogleUserProvider($service);

    expect($provider->getSourceName())->toBe('Google Workspace');
});

it('returns an empty list when google domain config is missing', function () {
    config(['google.domain' => null]);

    $service = Mockery::mock(GoogleWorkspaceService::class);
    $service->shouldNotReceive('listUsers');

    $provider = new GoogleUserProvider($service);

    expect($provider->getUsers())->toBe([]);
});

it('maps google user objects into normalized provider payloads', function () {
    config(['google.domain' => 'example.com']);

    $activeName = new class
    {
        public function getFullName(): string
        {
            return 'Alice Admin';
        }
    };

    $suspendedName = new class
    {
        public function getFullName(): string
        {
            return 'Bob Billing';
        }
    };

    $activeUser = new class($activeName)
    {
        public function __construct(private object $name) {}

        public function getName(): object
        {
            return $this->name;
        }

        public function getPrimaryEmail(): string
        {
            return 'alice@example.com';
        }

        public function getSuspended(): bool
        {
            return false;
        }

        public function getIsEnrolledIn2Sv(): bool
        {
            return true;
        }

        public function getLastLoginTime(): string
        {
            return '2026-03-25T18:00:00Z';
        }

        public function getOrgUnitPath(): string
        {
            return '/Admins';
        }
    };

    $suspendedUser = new class($suspendedName)
    {
        public function __construct(private object $name) {}

        public function getName(): object
        {
            return $this->name;
        }

        public function getPrimaryEmail(): string
        {
            return 'bob@example.com';
        }

        public function getSuspended(): bool
        {
            return true;
        }

        public function getIsEnrolledIn2Sv(): bool
        {
            return false;
        }

        public function getLastLoginTime(): string
        {
            return '2026-03-20T09:30:00Z';
        }

        public function getOrgUnitPath(): string
        {
            return '/Finance';
        }
    };

    $service = Mockery::mock(GoogleWorkspaceService::class);
    $service->shouldReceive('listUsers')
        ->once()
        ->with('example.com')
        ->andReturn([$activeUser, $suspendedUser]);

    $provider = new GoogleUserProvider($service);

    $users = $provider->getUsers();

    expect($users)->toHaveCount(2)
        ->and($users[0])->toMatchArray([
            'name' => 'Alice Admin',
            'email' => 'alice@example.com',
            'status' => 'Active',
            'is_2fa_enrolled' => true,
            'last_login' => '2026-03-25T18:00:00Z',
            'org_unit' => '/Admins',
        ])
        ->and($users[1])->toMatchArray([
            'name' => 'Bob Billing',
            'email' => 'bob@example.com',
            'status' => 'Suspended',
            'is_2fa_enrolled' => false,
            'last_login' => '2026-03-20T09:30:00Z',
            'org_unit' => '/Finance',
        ]);
});

it('rethrows service exceptions so registry can handle upstream failures', function () {
    config(['google.domain' => 'example.com']);

    $service = Mockery::mock(GoogleWorkspaceService::class);
    $service->shouldReceive('listUsers')
        ->once()
        ->with('example.com')
        ->andThrow(new Exception('Google API temporarily unavailable'));

    $provider = new GoogleUserProvider($service);

    expect(fn () => $provider->getUsers())
        ->toThrow(Exception::class, 'Google API temporarily unavailable');
});
