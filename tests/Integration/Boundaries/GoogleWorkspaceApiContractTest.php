<?php

declare(strict_types=1);

use Modules\GoogleAdmin\Services\GoogleUserProvider;
use Modules\GoogleAdmin\Services\GoogleWorkspaceService;

it('maps Google workspace user payloads into provider contract shape', function () {
    config()->set('google.domain', 'example.com');

    $fakeGoogleUser = new class
    {
        public function getName(): object
        {
            return new class
            {
                public function getFullName(): string
                {
                    return 'Ada Lovelace';
                }
            };
        }

        public function getPrimaryEmail(): string
        {
            return 'ada@example.com';
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
            return '2026-03-01T10:00:00Z';
        }

        public function getOrgUnitPath(): string
        {
            return '/Engineering';
        }
    };

    $service = new class([$fakeGoogleUser], null) extends GoogleWorkspaceService
    {
        public function __construct(private array $users, private ?\Throwable $error)
        {
            parent::__construct(app(\App\Services\RateLimiterService::class), app(\App\Services\CircuitBreakerService::class));
        }

        public function listUsers(string $domain, ?string $orgUnitPath = null): array
        {
            if ($this->error !== null) {
                throw $this->error;
            }

            return $this->users;
        }
    };

    $provider = new GoogleUserProvider($service);

    $users = $provider->getUsers();

    expect($users)->toHaveCount(1)
        ->and($users[0])->toMatchArray([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'status' => 'Active',
            'is_2fa_enrolled' => true,
            'org_unit' => '/Engineering',
        ]);
});

it('propagates Google boundary failures to callers', function () {
    config()->set('google.domain', 'example.com');

    $service = new class([], new RuntimeException('google boundary failure')) extends GoogleWorkspaceService
    {
        public function __construct(private array $users, private ?\Throwable $error)
        {
            parent::__construct(app(\App\Services\RateLimiterService::class), app(\App\Services\CircuitBreakerService::class));
        }

        public function listUsers(string $domain, ?string $orgUnitPath = null): array
        {
            if ($this->error !== null) {
                throw $this->error;
            }

            return $this->users;
        }
    };

    $provider = new GoogleUserProvider($service);

    expect(fn () => $provider->getUsers())
        ->toThrow(RuntimeException::class, 'google boundary failure');
});

it('maps partial Google user payloads without requiring full fields', function () {
    config()->set('google.domain', 'example.com');

    $partialGoogleUser = new class
    {
        public function getName(): object
        {
            return new class
            {
                public function getFullName(): string
                {
                    return 'Partial User';
                }
            };
        }

        public function getPrimaryEmail(): string
        {
            return 'partial@example.com';
        }

        public function getSuspended(): bool
        {
            return true;
        }

        public function getIsEnrolledIn2Sv(): bool
        {
            return false;
        }

        public function getLastLoginTime(): ?string
        {
            return null;
        }

        public function getOrgUnitPath(): string
        {
            return '/';
        }
    };

    $service = new class([$partialGoogleUser], null) extends GoogleWorkspaceService
    {
        public function __construct(private array $users, private ?\Throwable $error)
        {
            parent::__construct(app(\App\Services\RateLimiterService::class), app(\App\Services\CircuitBreakerService::class));
        }

        public function listUsers(string $domain, ?string $orgUnitPath = null): array
        {
            if ($this->error !== null) {
                throw $this->error;
            }

            return $this->users;
        }
    };

    $provider = new GoogleUserProvider($service);
    $users = $provider->getUsers();

    expect($users)->toHaveCount(1)
        ->and($users[0]['status'])->toBe('Suspended')
        ->and($users[0]['is_2fa_enrolled'])->toBeFalse();
});

it('keeps typed exception mapping consistent for non-runtime service errors', function () {
    config()->set('google.domain', 'example.com');

    $service = new class([], new InvalidArgumentException('invalid google payload')) extends GoogleWorkspaceService
    {
        public function __construct(private array $users, private ?\Throwable $error)
        {
            parent::__construct(app(\App\Services\RateLimiterService::class), app(\App\Services\CircuitBreakerService::class));
        }

        public function listUsers(string $domain, ?string $orgUnitPath = null): array
        {
            if ($this->error !== null) {
                throw $this->error;
            }

            return $this->users;
        }
    };

    $provider = new GoogleUserProvider($service);

    expect(fn () => $provider->getUsers())
        ->toThrow(InvalidArgumentException::class, 'invalid google payload');
});

it('fails fast on malformed Google user objects', function () {
    config()->set('google.domain', 'example.com');

    $service = new class([new stdClass], null) extends GoogleWorkspaceService
    {
        public function __construct(private array $users, private ?\Throwable $error)
        {
            parent::__construct(app(\App\Services\RateLimiterService::class), app(\App\Services\CircuitBreakerService::class));
        }

        public function listUsers(string $domain, ?string $orgUnitPath = null): array
        {
            if ($this->error !== null) {
                throw $this->error;
            }

            return $this->users;
        }
    };

    $provider = new GoogleUserProvider($service);

    expect(fn () => $provider->getUsers())
        ->toThrow(Error::class);
});
