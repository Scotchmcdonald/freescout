<?php

/**
 * Additional IntelliSense stubs for language servers that miss framework symbols.
 * This file is not intended to be autoloaded at runtime.
 */

namespace {
    if (! class_exists('IntellisensePestExpectation')) {
        class IntellisensePestExpectation
        {
            public function __construct(public mixed $value = null) {}

            public function __get(string $name): static
            {
                return $this;
            }

            public function and(mixed $value): static
            {
                return new static($value);
            }

            public function toBe(mixed $expected, string $message = ''): static { return $this; }
            public function toBeTrue(string $message = ''): static { return $this; }
            public function toBeFalse(string $message = ''): static { return $this; }
            public function toBeNull(string $message = ''): static { return $this; }
            public function toBeEmpty(string $message = ''): static { return $this; }
            public function toEqual(mixed $expected, string $message = ''): static { return $this; }
            public function toHaveCount(int $count, string $message = ''): static { return $this; }
            public function toBeInstanceOf(string $class, string $message = ''): static { return $this; }
            public function toThrow(string $class, ?string $message = null): static { return $this; }

            public function __call(string $name, array $arguments): static
            {
                return $this;
            }
        }
    }

    if (! interface_exists('PestDynamicTestContext')) {
        interface PestDynamicTestContext
        {
            public function __get(string $name): mixed;

            public function __set(string $name, mixed $value): void;
        }
    }

    if (! function_exists('uses')) {
        /** @param class-string ...$classAndTraits */
        function uses(string ...$classAndTraits): mixed
        {
            return null;
        }
    }

    if (! function_exists('it')) {
        /**
         * @param-closure-this \Tests\TestCase&\PestDynamicTestContext $closure
         */
        function it(string $description, ?\Closure $closure = null): mixed
        {
            return null;
        }
    }

    if (! function_exists('test')) {
        /**
         * @param-closure-this \Tests\TestCase&\PestDynamicTestContext $closure
         */
        function test(?string $description = null, ?\Closure $closure = null): mixed
        {
            return null;
        }
    }

    if (! function_exists('beforeAll')) {
        function beforeAll(\Closure $closure): void {}
    }

    if (! function_exists('beforeEach')) {
        /**
         * @param-closure-this \Tests\TestCase&\PestDynamicTestContext $closure
         */
        function beforeEach(?\Closure $closure = null): mixed
        {
            return null;
        }
    }

    if (! function_exists('afterEach')) {
        /**
         * @param-closure-this \Tests\TestCase&\PestDynamicTestContext $closure
         */
        function afterEach(?\Closure $closure = null): mixed
        {
            return null;
        }
    }

    if (! function_exists('afterAll')) {
        function afterAll(\Closure $closure): void {}
    }

    if (! function_exists('describe')) {
        /**
         * @param-closure-this \Tests\TestCase&\PestDynamicTestContext $closure
         */
        function describe(string $description, \Closure $tests): mixed
        {
            return null;
        }
    }

    if (! function_exists('dataset')) {
        /** @param \Closure|iterable<int|string, mixed> $dataset */
        function dataset(string $name, mixed $dataset): void {}
    }

    if (! function_exists('todo')) {
        function todo(string $description): mixed
        {
            return null;
        }
    }

    /**
     * @template TValue of mixed
     *
     * @param TValue|null $value
     */
    function expect(mixed $value = null): IntellisensePestExpectation
    {
        return new IntellisensePestExpectation($value);
    }

    if (! function_exists('data_get')) {
        function data_get(mixed $target, string|int|null $key, mixed $default = null): mixed
        {
            return $default;
        }
    }

    if (! function_exists('encrypt')) {
        function encrypt(mixed $value, bool $serialize = true): string
        {
            return '';
        }
    }

    if (! function_exists('decrypt')) {
        function decrypt(string $payload, bool $unserialize = true): mixed
        {
            return null;
        }
    }

    if (! function_exists('bcrypt')) {
        function bcrypt(string $value, array $options = []): string
        {
            return '';
        }
    }

    if (! function_exists('auth')) {
        /** @return \Illuminate\Contracts\Auth\Factory|\Illuminate\Contracts\Auth\StatefulGuard|\Illuminate\Contracts\Auth\Guard */
        function auth(?string $guard = null): mixed
        {
            return null;
        }
    }

    if (! class_exists('Config')) {
        class Config extends \Illuminate\Support\Facades\Config {}
    }
}

namespace Tests {
    if (false) {
        #[\AllowDynamicProperties]
        abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
        {
            public function __get(string $name): mixed
            {
                return null;
            }

            public function __set(string $name, mixed $value): void {}
        }
    }
}

namespace Modules\EmailMigration\Services {
    if (! function_exists(__NAMESPACE__.'\\data_get')) {
        function data_get(mixed $target, string|int|null $key, mixed $default = null): mixed
        {
            return \data_get($target, $key, $default);
        }
    }
}

namespace Modules\EmailMigration\Jobs {
    if (! function_exists(__NAMESPACE__.'\\decrypt')) {
        function decrypt(string $payload, bool $unserialize = true): mixed
        {
            return \decrypt($payload, $unserialize);
        }
    }
}

namespace Modules\KnowledgeBase\Services {
    if (! function_exists(__NAMESPACE__.'\\bcrypt')) {
        function bcrypt(string $value, array $options = []): string
        {
            return \bcrypt($value, $options);
        }
    }
}

namespace Modules\GoogleAdmin\Models {
    if (! function_exists(__NAMESPACE__.'\\encrypt')) {
        function encrypt(mixed $value, bool $serialize = true): string
        {
            return \encrypt($value, $serialize);
        }
    }

    if (! function_exists(__NAMESPACE__.'\\decrypt')) {
        function decrypt(string $payload, bool $unserialize = true): mixed
        {
            return \decrypt($payload, $unserialize);
        }
    }
}

namespace Illuminate\Support {
    if (! class_exists(ServiceProvider::class)) {
        abstract class ServiceProvider
        {
            protected mixed $app;

            public function register() {}

            public function boot() {}

            public function commands(array $commands): void {}

            public function publishes(array $paths, mixed $groups = null): void {}

            public function loadMigrationsFrom(array|string $paths): void {}

            public function loadViewsFrom(array|string $path, string $namespace): void {}
        }
    }

    if (! class_exists(Str::class)) {
        class Str
        {
            public static function lower(string $value): string
            {
                return $value;
            }
        }
    }
}

namespace Illuminate\Console {
    if (! class_exists(Command::class)) {
        abstract class Command
        {
            public const SUCCESS = 0;
            public const FAILURE = 1;

            public function option(?string $key = null): mixed
            {
                return null;
            }

            public function info(string $string, int|string|null $verbosity = null): void {}

            public function error(string $string, int|string|null $verbosity = null): void {}

            public function confirm(string $question, bool $default = false): bool
            {
                return true;
            }
        }
    }
}

namespace Illuminate\Console\Scheduling {
    if (! class_exists(Schedule::class)) {
        class Schedule {}
    }
}

namespace Illuminate\Auth\Access {
    if (! trait_exists(HandlesAuthorization::class)) {
        trait HandlesAuthorization {}
    }
}

namespace Illuminate\Database {
    if (! class_exists(Seeder::class)) {
        abstract class Seeder {}
    }
}

namespace Illuminate\Cache {
    if (! class_exists(RateLimiter::class)) {
        class RateLimiter
        {
            public function tooManyAttempts(string $key, int $maxAttempts): bool
            {
                return false;
            }

            public function availableIn(string $key): int
            {
                return 0;
            }

            public function hit(string $key, int $decaySeconds = 60): int
            {
                return 0;
            }
        }
    }
}

namespace Illuminate\Queue {
    if (! trait_exists(InteractsWithQueue::class)) {
        trait InteractsWithQueue
        {
            public function attempts(): int
            {
                return 1;
            }
        }
    }
}

namespace Illuminate\Notifications {
    if (! class_exists(Notification::class)) {
        class Notification {}
    }

    if (! class_exists(AnonymousNotifiable::class)) {
        class AnonymousNotifiable
        {
            public function notify(mixed $instance): void {}
        }
    }

    if (! class_exists(ChannelManager::class)) {
        class ChannelManager
        {
            public function route(string $channel, mixed $route): AnonymousNotifiable
            {
                return new AnonymousNotifiable;
            }
        }
    }
}

namespace Illuminate\Notifications\Messages {
    if (! class_exists(MailMessage::class)) {
        class MailMessage
        {
            public function line(string $line): static
            {
                return $this;
            }
        }
    }
}

namespace Webklex\PHPIMAP\Support {
    if (! class_exists(FlagCollection::class)) {
        class FlagCollection
        {
            public function toArray(): array
            {
                return [];
            }
        }
    }
}

namespace Carbon {
    if (! class_exists(Carbon::class)) {
        class Carbon
        {
            public static function now(mixed $tz = null): static
            {
                return new static;
            }

            public function subDays(int $days): static
            {
                return $this;
            }

            public function diffInSeconds(mixed $date = null, bool $absolute = true): int
            {
                return 0;
            }
        }
    }
}

namespace Illuminate\Database\Eloquent\Relations {
    if (! class_exists(BelongsToMany::class)) {
        class BelongsToMany
        {
            public function attach(mixed $id, array $attributes = [], bool $touch = true): void {}

            public function syncWithoutDetaching(array $ids): array
            {
                return [];
            }
        }
    }
}

namespace Illuminate\Database\Eloquent\Factories {
    if (! class_exists(Factory::class)) {
        abstract class Factory
        {
            /** @var object */
            protected $faker;

            public function __construct()
            {
                $this->faker = new class
                {
                    public string $slug = 'sample-slug';

                    public function numberBetween(int $min = 0, int $max = 100): int
                    {
                        return $min;
                    }
                };
            }
        }
    }
}

// ─── Mockery — fix @return self → @return static on chaining methods ─────────
//
// The vendor Mockery\LegacyMockInterface declares shouldIgnoreMissing() and
// makePartial() with `@return self`.  `self` in an interface context always
// resolves to the interface itself (LegacyMockInterface) and collapses the
// generic intersection type `LegacyMockInterface&MockInterface&T` that
// Mockery::mock() returns — causing Intelephense to report type mismatches
// wherever a mock is passed to a typed parameter.
//
// Declaring the interface here with `@return static` (or the `static` native
// return type) lets Intelephense resolve the return as the full intersection
// and carry `T` through the chain.
//
// NOTE: Intelephense merges multiple declarations of the same interface;
//       only the methods listed here need to differ from the vendor copy.

namespace Mockery {
    interface LegacyMockInterface
    {
        public function makePartial(): static;

        /**
         * @param  mixed  $returnValue
         * @param  mixed  $mock
         */
        public function shouldIgnoreMissing($returnValue = null, $mock = null): static;

        public function byDefault(): static;

        public function shouldAllowMockingProtectedMethods(): static;
    }
}
