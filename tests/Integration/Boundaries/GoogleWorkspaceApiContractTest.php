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

it('returns empty array without exception when Google domain config is absent', function () {
    // Unset domain so the provider cannot determine which domain to list
    config()->set('google.domain', null);

    $service = new class([], null) extends GoogleWorkspaceService
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

    // Provider contract: must return empty array gracefully, not throw when domain
    // config is absent — callers must not be forced to guard every call site
    $result = $provider->getUsers();

    expect($result)->toBe([]);
});

it('maps empty Google user list to empty array without exception', function () {
    config()->set('google.domain', 'example.com');

    $service = new class([], null) extends GoogleWorkspaceService
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

    // An empty workspace must map to [] not null — typed array return contract
    $users = $provider->getUsers();

    expect($users)->toBe([]);
});

it('fails fast when a mixed user list contains a malformed first element', function () {
    config()->set('google.domain', 'example.com');

    $validUser = new class
    {
        public function getName(): object
        {
            return new class
            {
                public function getFullName(): string
                {
                    return 'Valid User';
                }
            };
        }

        public function getPrimaryEmail(): string
        {
            return 'valid@example.com';
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
            return '/Ops';
        }
    };

    $service = new class([new stdClass, $validUser], null) extends GoogleWorkspaceService
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

    // Mutation guard: malformed user object should fail fast, not silently skip and continue.
    expect(fn () => $provider->getUsers())
        ->toThrow(Error::class);
});
