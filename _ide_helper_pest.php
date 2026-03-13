<?php

/**
 * IDE helper stubs for symbols that language servers miss.
 *
 * Three categories of symbols are covered here:
 *
 * 1. Composer "files" autoload — functions/classes loaded outside PSR-4
 *    (Pest global functions, Mockery facade, Laravel global helpers such as
 *    config()). PSR-4-aware language servers never index these files.
 *
 * 2. Large vendor classes that some ARM64/memory-constrained language servers
 *    skip (e.g. Illuminate\Foundation\Application, Illuminate\Contracts\Console\Kernel).
 *
 * All declarations are wrapped in `if (false)` so they are NEVER executed at
 * runtime. They exist purely so static-analysis tooling (Intelephense, etc.)
 * can resolve the symbols.
 *
 * DO NOT load this file at runtime.
 *
 * @see vendor/pestphp/pest/src/Functions.php
 * @see vendor/mockery/mockery/library/Mockery.php
 * @see vendor/laravel/framework/src/Illuminate/Foundation/helpers.php
 */

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Global namespace — Pest functions, Mockery class, Laravel helpers       │
// └──────────────────────────────────────────────────────────────────────────┘

namespace {
    if (false) {
        // ── Pest global functions ─────────────────────────────────────────

        /** @param class-string ...$args */
        function uses(string ...$args): mixed
        {
            throw new \BadMethodCallException('stub');
        }

        function it(string $description, ?\Closure $closure = null): mixed
        {
            throw new \BadMethodCallException('stub');
        }

        function test(string $description, ?\Closure $closure = null): mixed
        {
            throw new \BadMethodCallException('stub');
        }

        /**
         * @template TValue
         *
         * @param  TValue  $value
         */
        function expect(mixed $value): mixed
        {
            throw new \BadMethodCallException('stub');
        }

        function beforeAll(\Closure $closure): void {}
        function beforeEach(\Closure $closure): void {}
        function afterEach(\Closure $closure): void {}
        function afterAll(\Closure $closure): void {}

        function describe(string $description, \Closure $closure): mixed
        {
            throw new \BadMethodCallException('stub');
        }

        /** @param \Closure|iterable<int|string, mixed> $dataset */
        function dataset(string $name, mixed $dataset): void {}

        function todo(string $description): mixed
        {
            throw new \BadMethodCallException('stub');
        }

        // ── Mockery (no namespace, files autoload) ────────────────────────

        class Mockery
        {
            public static function mock(mixed ...$args): mixed
            {
                throw new \BadMethodCallException('stub');
            }

            public static function spy(mixed ...$args): mixed
            {
                throw new \BadMethodCallException('stub');
            }

            public static function namedMock(mixed ...$args): mixed
            {
                throw new \BadMethodCallException('stub');
            }

            public static function close(): void {}
        }

        // ── Laravel global helpers (files autoload, if (! function_exists)) ─

        /**
         * Get / set the specified configuration value.
         *
         * @param  array<string, mixed>|string|null  $key
         */
        function config(array|string|null $key = null, mixed $default = null): mixed
        {
            return null;
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Global namespace continued — app(), __(), now()                         │
// └──────────────────────────────────────────────────────────────────────────┘

namespace {
    if (false) {
        /** Resolve a service from the container, or return the Application. */
        function app(?string $abstract = null, array $parameters = []): mixed
        {
            return null;
        }

        /** Get the path to the database directory or a file inside it. */
        function database_path(string $path = ''): string
        {
            return $path;
        }

        /** Get the path to the project root or a file inside it. */
        function base_path(string $path = ''): string
        {
            return $path;
        }

        /** Get the path to the storage directory or a file inside it. */
        function storage_path(string $path = ''): string
        {
            return $path;
        }

        /** Get the path to the public directory or a file inside it. */
        function public_path(string $path = ''): string
        {
            return $path;
        }

        /** Get the path to the resources directory or a file inside it. */
        function resource_path(string $path = ''): string
        {
            return $path;
        }

        /** Translate the given string. */
        function __(?string $key = null, array $replace = [], ?string $locale = null): mixed
        {
            return null;
        }

        /** Render a view instance. */
        function view(?string $view = null, array $data = [], array $mergeData = []): mixed
        {
            return null;
        }

        /** Resolve auth manager / guard. */
        function auth(?string $guard = null): mixed
        {
            return new class
            {
                public function user(): mixed
                {
                    return null;
                }

                public function id(): mixed
                {
                    return null;
                }
            };
        }

        /** Resolve the current request instance. */
        function request(?string $key = null, mixed $default = null): mixed
        {
            return new class
            {
                public function getHost(): string
                {
                    return '';
                }

                public function segment(int $index, ?string $default = null): ?string
                {
                    return $default;
                }

                public function routeIs(mixed ...$patterns): bool
                {
                    return false;
                }
            };
        }

        /** Resolve the cache repository / manager. */
        function cache(?string $key = null, mixed $default = null): mixed
        {
            return new class
            {
                public function get(string $key, mixed $default = null): mixed
                {
                    return $default;
                }

                public function put(string $key, mixed $value, mixed $ttl = null): bool
                {
                    return true;
                }

                public function forever(string $key, mixed $value): bool
                {
                    return true;
                }

                public function forget(string $key): bool
                {
                    return true;
                }

                public function remember(string $key, mixed $ttl, callable $callback): mixed
                {
                    return $callback();
                }
            };
        }

        /** Resolve session store / manager. */
        function session(mixed ...$args): object
        {
            return new class
            {
                public function put(string|array $key, mixed $value = null): void {}
                public function get(string $key, mixed $default = null): mixed { return $default; }
            };
        }

        /** Resolve DB manager / connection. */
        function db(?string $connection = null): mixed
        {
            return null;
        }

        /** Resolve logger instance or write a log message. */
        function logger(mixed ...$args): object
        {
            return new class
            {
                public function info(string $message, array $context = []): void {}
                public function warning(string $message, array $context = []): void {}
                public function error(string $message, array $context = []): void {}
                public function debug(string $message, array $context = []): void {}
            };
        }

        /** Create a redirector response factory. */
        function redirect(?string $to = null, int $status = 302, array $headers = [], bool $secure = null): object
        {
            return new class
            {
                public function back(int $status = 302, array $headers = [], mixed $fallback = false): object
                {
                    return $this;
                }

                public function route(string $route, mixed $parameters = [], int $status = 302, array $headers = []): object
                {
                    return $this;
                }

                public function with(string|array $key, mixed $value = null): object
                {
                    return $this;
                }
            };
        }

        /** Redirect back helper. */
        function back(int $status = 302, array $headers = [], mixed $fallback = false): object
        {
            return redirect()->back($status, $headers, $fallback);
        }

        /** Dispatch an event object. */
        function event(mixed ...$args): mixed
        {
            return null;
        }

        /** Create a collection from the given value. */
        function collect(mixed $value = null): \Illuminate\Support\Collection
        {
            return new \Illuminate\Support\Collection;
        }

        /** Start an activity log entry. */
        function activity(?string $logName = null): object
        {
            return new class
            {
                public function causedBy(mixed $subject): static
                {
                    return $this;
                }

                public function performedOn(mixed $subject): static
                {
                    return $this;
                }

                public function withProperties(array $properties): static
                {
                    return $this;
                }

                public function log(string $description): mixed
                {
                    return null;
                }
            };
        }

        /** Create a response factory. */
        function response(mixed $content = '', int $status = 200, array $headers = []): object
        {
            return new class
            {
                public function json(array $data = [], int $status = 200, array $headers = [], int $options = 0): object
                {
                    return $this;
                }
            };
        }

        /** Throw an HTTP exception. */
        function abort(int $code, string $message = '', array $headers = []): never
        {
            throw new \RuntimeException($message !== '' ? $message : "HTTP {$code}");
        }

        /** Create a Carbon instance for the current date/time. */
        function now(mixed $tz = null): object
        {
            throw new \BadMethodCallException('stub');
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Foundation — Application                                     │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Foundation {
    if (false) {
        /**
         * Minimal stub so language servers that do not index vendor can still
         * resolve \Illuminate\Foundation\Application as a type.
         */
        class Application
        {
            public function make(string $abstract, array $parameters = []): mixed
            {
                return null;
            }

            public function singleton(string $abstract, mixed $concrete = null): void {}

            public function alias(string $abstract, string $alias): void {}

            public function instance(string $abstract, mixed $instance): mixed
            {
                return $instance;
            }

            public function isProduction(): bool
            {
                return false;
            }

            public function runningUnitTests(): bool
            {
                return false;
            }

            /** @return mixed */
            public function bound(string $abstract): bool
            {
                return false;
            }
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Contracts\Console — Kernel interface                          │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Contracts\Console {
    if (false) {
        /**
         * Minimal stub for \Illuminate\Contracts\Console\Kernel.
         */
        interface Kernel
        {
            public function bootstrap(): void;
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Support — ServiceProvider                                    │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Support {
    if (false) {
        class Carbon
        {
            public static function today(mixed $tz = null): static
            {
                return new static;
            }

            public static function now(mixed $tz = null): static
            {
                return new static;
            }

            public static function parse(string $time = '', mixed $tz = null): static
            {
                return new static;
            }

            public function startOfDay(): static
            {
                return $this;
            }

            public function endOfDay(): static
            {
                return $this;
            }

            public function startOfWeek(): static
            {
                return $this;
            }

            public function addHour(): static
            {
                return $this;
            }

            public function addHours(int $hours): static
            {
                return $this;
            }

            public function lt(mixed $date): bool
            {
                return false;
            }

            public function isFuture(): bool
            {
                return false;
            }

            public function diffInMinutes(mixed $date = null, bool $absolute = true): int
            {
                return 0;
            }

            public function toIso8601String(): string
            {
                return '';
            }
        }

        class Stringable
        {
            public function __construct(protected string $value = '') {}

            public function value(): string
            {
                return $this->value;
            }
        }

        class Collection
        {
            public function filter(?callable $callback = null): static
            {
                return $this;
            }

            public function map(callable $callback): static
            {
                return $this;
            }

            public function keyBy(string|callable $keyBy): static
            {
                return $this;
            }

            public function get(mixed $key, mixed $default = null): mixed
            {
                return $default;
            }

            public function toArray(): array
            {
                return [];
            }

            public function count(): int
            {
                return 0;
            }

            public function isEmpty(): bool
            {
                return true;
            }

            public function withQueryString(): static
            {
                return $this;
            }

            public function values(): static
            {
                return $this;
            }
        }

        class Str
        {
            public static function slug(string $title, string $separator = '-', ?string $language = 'en'): string
            {
                return '';
            }

            public static function upper(string $value): string
            {
                return $value;
            }

            public static function random(int $length = 16): string
            {
                return '';
            }

            public static function uuid(): object
            {
                return new class
                {
                    public function toString(): string
                    {
                        return '';
                    }
                };
            }
        }

        abstract class ServiceProvider
        {
            /** @var \Illuminate\Foundation\Application */
            protected $app;

            public function register(): void {}

            public function boot(): void {}
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Foundation — AliasLoader                                     │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Foundation {
    if (false) {
        class AliasLoader
        {
            public static function getInstance(array $aliases = []): static
            {
                throw new \BadMethodCallException('stub');
            }

            public function alias(string $class, string $alias): void {}
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Console\Events — CommandStarting                             │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Console\Events {
    if (false) {
        class CommandStarting
        {
            public string $command;
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Database — SQLiteConnection                                  │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Database {
    if (false) {
        class DatabaseManager
        {
            public function beginTransaction(): void {}
            public function commit(): void {}
            public function rollBack(): void {}

            public function getSchemaBuilder(): object
            {
                return new class
                {
                    public function hasTable(string $table): bool
                    {
                        return false;
                    }
                };
            }

            public function table(string $table): \Illuminate\Database\Eloquent\Builder
            {
                return new \Illuminate\Database\Eloquent\Builder;
            }
        }

        class SQLiteConnection
        {
            public function getPdo(): \PDO
            {
                throw new \BadMethodCallException('stub');
            }
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Queue\Events — JobProcessed                                  │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Queue\Events {
    if (false) {
        class JobProcessed
        {
            public mixed $job;
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Cache\RateLimiting — Limit                                   │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Cache\RateLimiting {
    if (false) {
        class Limit
        {
            public static function perMinute(int $maxAttempts, int $decayMinutes = 1): static
            {
                throw new \BadMethodCallException('stub');
            }

            public function by(mixed $key): static
            {
                throw new \BadMethodCallException('stub');
            }
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Database\Migrations — Migration                              │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Database\Migrations {
    if (false) {
        abstract class Migration
        {
            public function up(): void {}

            public function down(): void {}
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Database\Schema — Blueprint                                  │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Database\Schema {
    if (false) {
        class Blueprint
        {
            public function string(string $column, int $length = 255): mixed
            {
                return null;
            }

            public function integer(string $column, bool $autoIncrement = false, bool $unsigned = false): mixed
            {
                return null;
            }

            public function bigInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): mixed
            {
                return null;
            }

            public function boolean(string $column): mixed
            {
                return null;
            }

            public function text(string $column): mixed
            {
                return null;
            }

            public function longText(string $column): mixed
            {
                return null;
            }

            public function json(string $column): mixed
            {
                return null;
            }

            public function timestamp(string $column, int $precision = 0): mixed
            {
                return null;
            }

            public function timestamps(int $precision = 0): void {}

            public function softDeletes(string $column = 'deleted_at', int $precision = 0): mixed
            {
                return null;
            }

            public function enum(string $column, array $allowed): mixed
            {
                return null;
            }

            public function foreignId(string $column): mixed
            {
                return null;
            }

            public function unsignedBigInteger(string $column, bool $autoIncrement = false): mixed
            {
                return null;
            }

            public function dropColumn(mixed $columns): void {}

            public function dropForeign(mixed $index): void {}

            public function index(mixed $columns = null, ?string $name = null, ?string $algorithm = null): void {}

            public function unique(mixed $columns = null, ?string $name = null, ?string $algorithm = null): void {}

            public function foreign(string $column, ?string $name = null): mixed
            {
                return null;
            }

            public function nullable(bool $value = true): static
            {
                return $this;
            }

            public function default(mixed $value): static
            {
                return $this;
            }

            public function after(string $column): static
            {
                return $this;
            }

            public function change(): void {}

            public function constrained(?string $table = null, ?string $column = null): static
            {
                return $this;
            }

            public function onDelete(string $action): static
            {
                return $this;
            }

            public function onUpdate(string $action): static
            {
                return $this;
            }

            public function dropIndex(mixed $index): void {}
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  App\Services — EntitlementEngine (container alias, no PHP file)         │
// └──────────────────────────────────────────────────────────────────────────┘

namespace App\Services {
    if (false) {
        /**
         * Stub for the EntitlementEngine container alias.
         * The concrete implementation is \Modules\PIB\Services\EntitlementEngineService,
         * registered via $app->alias() in AppServiceProvider — no PHP source file exists.
         */
        class EntitlementEngine {}
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Foundation\Testing — TestCase and traits                     │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Foundation\Testing {
    if (false) {
        abstract class TestCase
        {
            /** @var \Illuminate\Foundation\Application */
            protected $app;

            protected function setUp(): void {}

            protected function tearDown(): void {}

            protected function fail(string $message = ''): never
            {
                throw new \RuntimeException($message);
            }

            protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void {}

            public function artisan(string $command, array $parameters = []): mixed
            {
                return null;
            }

            /** @return array<string, mixed> */
            protected function setUpTraits()
            {
                return [];
            }
        }

        trait RefreshDatabase {}
        trait WithFaker {}
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Support\Facades — Storage and ParallelTesting                │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Support\Facades {
    if (false) {
        class Redis
        {
            public static function connection(?string $name = null): object
            {
                return new class
                {
                    public function ping(): mixed
                    {
                        return null;
                    }
                };
            }
        }

        class Storage
        {
            public static function fake(?string $disk = null): mixed
            {
                return null;
            }
        }

        class ParallelTesting
        {
            public static function token(): ?string
            {
                return null;
            }

            public static function setUpTestDatabase(\Closure $callback): void {}
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  TorMorten\Eventy — Events                                               │
// └──────────────────────────────────────────────────────────────────────────┘

namespace TorMorten\Eventy {
    if (false) {
        class Events {}
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Symfony\Component\Process — Process                                     │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Symfony\Component\Process {
    if (false) {
        class Process
        {
            public function __construct(array $command, ?string $cwd = null, ?array $env = null, mixed $input = null, ?float $timeout = 60) {}

            public function setEnv(array $env): static
            {
                return $this;
            }

            public function setTimeout(?float $timeout): static
            {
                return $this;
            }

            public function disableOutput(): static
            {
                return $this;
            }

            public function start(callable $callback = null, array $env = []): void {}

            public function wait(callable $callback = null): int
            {
                return 0;
            }

            public function run(callable $callback = null, array $env = []): int
            {
                return 0;
            }

            public function isSuccessful(): bool
            {
                return true;
            }

            public function isRunning(): bool
            {
                return false;
            }

            public function stop(float $timeout = 10, int $signal = null): ?int
            {
                return 0;
            }

            public function getOutput(): string
            {
                return '';
            }

            public function getErrorOutput(): string
            {
                return '';
            }
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Symfony\Component\Console\Output — BufferedOutput                     │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Symfony\Component\Console\Output {
    if (false) {
        class BufferedOutput
        {
            public function fetch(): string
            {
                return '';
            }
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Database\Eloquent — Model                                    │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Database\Eloquent {
    if (false) {
        class Builder
        {
            public function where(string|\Closure $column, mixed $operator = null, mixed $value = null): static
            {
                return $this;
            }

            public function orWhere(string|\Closure $column, mixed $operator = null, mixed $value = null): static
            {
                return $this;
            }

            public function whereIn(string $column, array $values): static
            {
                return $this;
            }

            public function whereBetween(string $column, array $values): static
            {
                return $this;
            }

            public function whereNull(string $column): static
            {
                return $this;
            }

            public function whereNotNull(string $column): static
            {
                return $this;
            }

            public function latest(?string $column = null): static
            {
                return $this;
            }

            public function orderBy(string $column, string $direction = 'asc'): static
            {
                return $this;
            }

            public function limit(int $value): static
            {
                return $this;
            }

            public function with(string|array $relations): static
            {
                return $this;
            }

            public function paginate(int $perPage = 15): mixed
            {
                return null;
            }

            public function get(array $columns = ['*']): mixed
            {
                return null;
            }

            public function count(string $columns = '*'): int
            {
                return 0;
            }

            public function sum(string $column): int|float
            {
                return 0;
            }

            public function pluck(string $column, ?string $key = null): mixed
            {
                return null;
            }

            public function value(string $column): mixed
            {
                return null;
            }

            public function exists(): bool
            {
                return false;
            }

            public function findOrFail(int|string $id, array $columns = ['*']): mixed
            {
                return null;
            }

            public function first(array $columns = ['*']): mixed
            {
                return null;
            }

            public function firstOrFail(array $columns = ['*']): mixed
            {
                return null;
            }

            public function delete(): int
            {
                return 0;
            }

            public function withQueryString(): static
            {
                return $this;
            }
        }

        abstract class Model
        {
            protected static function boot() {}

            public static function creating(callable $callback): void {}

            public static function factory(mixed ...$parameters): mixed
            {
                return null;
            }

            public static function query(): Builder
            {
                return new Builder;
            }

            public static function with(string|array $relations): Builder
            {
                return new Builder;
            }

            public static function latest(?string $column = null): Builder
            {
                return new Builder;
            }

            public static function orderBy(string $column, string $direction = 'asc'): Builder
            {
                return new Builder;
            }

            public static function where(string $column, mixed $operator = null, mixed $value = null): Builder
            {
                return new Builder;
            }

            public static function whereIn(string $column, array $values): Builder
            {
                return new Builder;
            }

            public static function pluck(string $column, ?string $key = null): mixed
            {
                return null;
            }

            public static function create(array $attributes = []): static
            {
                return new static;
            }

            public static function firstOrCreate(array $attributes = [], array $values = []): static
            {
                return new static;
            }

            public static function updateOrCreate(array $attributes = [], array $values = []): static
            {
                return new static;
            }

            public static function __callStatic(string $method, array $parameters): mixed
            {
                return new Builder;
            }

            public static function findOrFail(int|string $id, array $columns = ['*']): static
            {
                return new static;
            }

            public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null, ?string $relation = null): \Illuminate\Database\Eloquent\Relations\BelongsTo
            {
                return new \Illuminate\Database\Eloquent\Relations\BelongsTo;
            }

            public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): \Illuminate\Database\Eloquent\Relations\HasMany
            {
                return new \Illuminate\Database\Eloquent\Relations\HasMany;
            }

            public function update(array $attributes = [], array $options = []): bool
            {
                return true;
            }

            public function save(array $options = []): bool
            {
                return true;
            }

            public function __get(string $key): mixed
            {
                return null;
            }

            public function __set(string $key, mixed $value): void {}

            public function __call(string $method, array $parameters): mixed
            {
                return null;
            }
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Database\Eloquent\Relations — BelongsTo / HasMany          │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Database\Eloquent\Relations {
    if (false) {
        class BelongsTo {}
        class HasMany
        {
            public function delete(): int
            {
                return 0;
            }
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Database\Eloquent\Factories — Factory / HasFactory          │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Database\Eloquent\Factories {
    if (false) {
        abstract class Factory
        {
            public static function new(array $attributes = []): static
            {
                throw new \BadMethodCallException('stub');
            }
        }

        trait HasFactory {}
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Http — Request / Responses                                   │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Http {
    if (false) {
        class ResponseFactory
        {
            public function json(array $data = [], int $status = 200, array $headers = [], int $options = 0): JsonResponse
            {
                return new JsonResponse;
            }

            public function download(string $file, ?string $name = null, array $headers = [], ?string $disposition = null): \Symfony\Component\HttpFoundation\BinaryFileResponse
            {
                return new \Symfony\Component\HttpFoundation\BinaryFileResponse;
            }
        }

        class Request
        {
            public function filled(string $key): bool
            {
                return false;
            }

            public function user(?string $guard = null): mixed
            {
                return null;
            }

            public function query(?string $key = null, mixed $default = null): mixed
            {
                return $default;
            }

            public function string(string $key, string $default = ''): \Illuminate\Support\Stringable
            {
                return new \Illuminate\Support\Stringable($default);
            }

            public function input(string $key = null, mixed $default = null): mixed
            {
                return $default;
            }

            public function only(array|string $keys): array
            {
                return [];
            }

            public function get(string $key, mixed $default = null): mixed
            {
                return $default;
            }

            public function has(string $key): bool
            {
                return false;
            }

            public function wantsJson(): bool
            {
                return false;
            }

            public function validate(array $rules, ...$params): array
            {
                return [];
            }

            public function __get(string $name): mixed
            {
                return null;
            }
        }

        class JsonResponse {}
        class RedirectResponse {}
        class Response {}
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Http\Client — Factory / Response                            │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Http\Client {
    if (false) {
        class Response
        {
            public function header(string $header): mixed
            {
                return null;
            }

            public function json(?string $key = null, mixed $default = null): mixed
            {
                return $default;
            }

            public function successful(): bool
            {
                return true;
            }

            public function failed(): bool
            {
                return false;
            }

            public function status(): int
            {
                return 200;
            }

            public function body(): string
            {
                return '';
            }

            public function throw(): static
            {
                return $this;
            }
        }

        class Factory
        {
            public function asForm(): static
            {
                return $this;
            }

            public function withHeaders(array $headers): static
            {
                return $this;
            }

            public function post(string $url, array $data = []): Response
            {
                return new Response;
            }

            public function get(string $url, array $query = []): Response
            {
                return new Response;
            }
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Contracts\View — View / Factory                             │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Contracts\View {
    if (false) {
        interface View {}
        interface Factory {}
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Foundation\Auth\Access / Validation traits                  │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Foundation\Auth\Access {
    if (false) {
        trait AuthorizesRequests
        {
            public function authorize(mixed $ability, mixed $arguments = []): mixed
            {
                return null;
            }
        }
    }
}

namespace Illuminate\Foundation\Validation {
    if (false) {
        trait ValidatesRequests {}
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Foundation\Bus — Dispatchable                               │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Foundation\Bus {
    if (false) {
        trait Dispatchable
        {
            public static function dispatch(mixed ...$arguments): mixed
            {
                return null;
            }
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Spatie\Activitylog\Models — Activity                                    │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Spatie\Activitylog\Models {
    if (false) {
        class Activity extends \Illuminate\Database\Eloquent\Model
        {
            public static function inLog(string $logName): \Illuminate\Database\Eloquent\Builder
            {
                return new \Illuminate\Database\Eloquent\Builder;
            }
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Symfony\Component\HttpFoundation — BinaryFileResponse                  │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Symfony\Component\HttpFoundation {
    if (false) {
        class BinaryFileResponse {}
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Carbon — Carbon                                                         │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Carbon {
    if (false) {
        class Carbon
        {
            public static function today(mixed $tz = null): static
            {
                return new static;
            }

            public static function now(mixed $tz = null): static
            {
                return new static;
            }

            public static function parse(string $time = '', mixed $tz = null): static
            {
                return new static;
            }

            public function startOfDay(): static
            {
                return $this;
            }

            public function endOfDay(): static
            {
                return $this;
            }

            public function startOfWeek(): static
            {
                return $this;
            }

            public function isFuture(): bool
            {
                return false;
            }

            public function diffInMinutes(mixed $date = null, bool $absolute = true): int
            {
                return 0;
            }

            public function toString(): string
            {
                return '';
            }
        }
    }
}

namespace Illuminate\Foundation\Auth\Access {
    trait AuthorizesRequests
    {
        public function authorize(mixed $ability, mixed $arguments = []): mixed
        {
            return null;
        }
    }
}

namespace Illuminate\Foundation\Validation {
    trait ValidatesRequests {}
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\Process — Factory / PendingProcess                           │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\Process {
    if (false) {
        class PendingProcess
        {
            public function env(array $env): static
            {
                return $this;
            }

            public function timeout(int|float|null $seconds): static
            {
                return $this;
            }

            public function run(string|array $command, callable $output = null): object
            {
                return new class
                {
                    public function successful(): bool
                    {
                        return true;
                    }

                    public function output(): string
                    {
                        return '';
                    }

                    public function errorOutput(): string
                    {
                        return '';
                    }
                };
            }
        }

        class Factory
        {
            public function newPendingProcess(): PendingProcess
            {
                return new PendingProcess;
            }
        }
    }
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Illuminate\View — View                                                  │
// └──────────────────────────────────────────────────────────────────────────┘

namespace Illuminate\View {
    if (false) {
        interface View {}
    }
}
