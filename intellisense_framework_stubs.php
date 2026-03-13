<?php

namespace {
    class View
    {
        public static function make(string $view, array $data = [], array $mergeData = []): object
        {
            return new class
            {
                public function render(): string
                {
                    return '';
                }
            };
        }
    }

    function data_get(mixed $target, string|int|null $key, mixed $default = null): mixed
    {
        return $default;
    }

    function encrypt(mixed $value, bool $serialize = true): string
    {
        return '';
    }

    function decrypt(string $payload, bool $unserialize = true): mixed
    {
        return null;
    }

    function bcrypt(string $value, array $options = []): string
    {
        return '';
    }
}

namespace Modules\EmailMigration\Services {
    function data_get(mixed $target, string|int|null $key, mixed $default = null): mixed
    {
        return \data_get($target, $key, $default);
    }
}

namespace Modules\EmailMigration\Jobs {
    function decrypt(string $payload, bool $unserialize = true): mixed
    {
        return \decrypt($payload, $unserialize);
    }

    function rand(int $min, int $max): int
    {
        return $min;
    }
}

namespace Modules\EmailMigration\Http\Controllers {
    function rand(int $min, int $max): int
    {
        return $min;
    }

    function view(mixed $view = null, array $data = [], array $mergeData = []): mixed
    {
        return \view($view, $data, $mergeData);
    }
}

namespace Modules\KnowledgeBase\Services {
    function bcrypt(string $value, array $options = []): string
    {
        return \bcrypt($value, $options);
    }
}

namespace Modules\GoogleAdmin\Models {
    function encrypt(mixed $value, bool $serialize = true): string
    {
        return \encrypt($value, $serialize);
    }

    function decrypt(string $payload, bool $unserialize = true): mixed
    {
        return \decrypt($payload, $unserialize);
    }
}

namespace Modules\GoogleAdmin\Services {
    function json_validate(string $json, int $depth = 512, int $flags = 0): bool
    {
        return true;
    }
}

namespace Modules\Crm\Http\Controllers {
    function view(mixed $view = null, array $data = [], array $mergeData = []): mixed
    {
        return \view($view, $data, $mergeData);
    }
}

namespace Modules\Crm\Providers {
    function config_path(string $path = ''): string
    {
        return $path;
    }
}

namespace Illuminate\Support {
    abstract class ServiceProvider
    {
        protected mixed $app;

        public function register() {}

        public function boot() {}

        public function commands(array $commands): void {}

        public function publishes(array $paths, mixed $groups = null): void {}

        public function mergeConfigFrom(string $path, string $key): void {}

        public function loadMigrationsFrom(array|string $paths): void {}

        public function loadViewsFrom(array|string $path, string $namespace): void {}

        public function loadJsonTranslationsFrom(string $path): void {}
    }

    class Str
    {
        public static function lower(string $value): string
        {
            return $value;
        }

        public static function startsWith(string $haystack, string|array $needles): bool
        {
            return false;
        }
    }
}

namespace Illuminate\Console {
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

        public function warn(string $string, int|string|null $verbosity = null): void {}

        public function table(array $headers, array $rows): void {}

        public function line(string $string, int|string|null $verbosity = null): void {}

        public function comment(string $string, int|string|null $verbosity = null): void {}

        public function newLine(int $count = 1): void {}

        public function confirm(string $question, bool $default = false): bool
        {
            return true;
        }
    }
}

namespace Illuminate\Console\Scheduling {
    class Schedule {}
}

namespace Illuminate\Auth\Access {
    trait HandlesAuthorization {}
}

namespace Illuminate\Database {
    abstract class Seeder
    {
        public ?\Illuminate\Console\Command $command = null;
    }

    class Connection
    {
        public function table(string $table): \Illuminate\Database\Query\Builder
        {
            throw new \BadMethodCallException('stub');
        }

        public function getDriverName(): string
        {
            return 'mysql';
        }

        public function getPdo(): mixed
        {
            return null;
        }
    }
}

namespace Illuminate\Cache {
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

namespace Illuminate\Queue {
    trait InteractsWithQueue
    {
        public function attempts(): int
        {
            return 1;
        }
    }
}

namespace Illuminate\Bus {
    class Batch
    {
        public int|string $id = 0;

        public function cancelled(): bool
        {
            return false;
        }
    }

    class PendingBatch
    {
        public function name(string $name): static
        {
            return $this;
        }

        public function then(callable $callback): static
        {
            return $this;
        }

        public function catch(callable $callback): static
        {
            return $this;
        }

        public function progress(callable $callback): static
        {
            return $this;
        }

        public function finally(callable $callback): static
        {
            return $this;
        }

        public function onQueue(string $queue): static
        {
            return $this;
        }

        public function dispatch(): Batch
        {
            throw new \BadMethodCallException('stub');
        }
    }

    trait Batchable
    {
        public function batch(): ?Batch
        {
            return null;
        }
    }
}

namespace Illuminate\Contracts\Bus {
    interface Dispatcher
    {
        public function batch(array $jobs): \Illuminate\Bus\PendingBatch;
    }
}

namespace Illuminate\Foundation\Bus {
    class PendingDispatch
    {
        public function onQueue(string $queue): static
        {
            return $this;
        }
    }

    trait Dispatchable
    {
        public static function dispatch(mixed ...$arguments): PendingDispatch
        {
            throw new \BadMethodCallException('stub');
        }

        public static function dispatchSync(mixed ...$arguments): mixed
        {
            return null;
        }
    }
}

namespace Illuminate\Foundation\Events {
    trait Dispatchable
    {
        public static function dispatch(mixed ...$arguments): mixed
        {
            return null;
        }
    }
}

namespace Illuminate\Notifications {
    class Notification {}

    class AnonymousNotifiable
    {
        public function notify(mixed $instance): void {}
    }

    class ChannelManager
    {
        public function route(string $channel, mixed $route): AnonymousNotifiable
        {
            return new AnonymousNotifiable;
        }
    }
}

namespace Illuminate\Support\Facades {
    class Notification
    {
        public static function route(string $channel, mixed $route): \Illuminate\Notifications\AnonymousNotifiable
        {
            return new \Illuminate\Notifications\AnonymousNotifiable;
        }
    }

    class View
    {
        public static function make(string $view, array $data = [], array $mergeData = []): object
        {
            return new class
            {
                public function render(): string
                {
                    return '';
                }
            };
        }
    }
}

namespace Illuminate\Notifications\Messages {
    class MailMessage
    {
        public function line(string $line): static
        {
            return $this;
        }
    }
}

namespace Illuminate\Routing {
    abstract class Controller {}
}

namespace Symfony\Component\HttpFoundation {
    class Response {}

    class StreamedResponse {}
}

namespace Illuminate\Http {
    class UploadedFile
    {
        public function getRealPath(): string
        {
            return '';
        }

        public function store(string $path = '', ?string $disk = null): string
        {
            return '';
        }
    }

    class Request
    {
        public function hasFile(string $key): bool
        {
            return false;
        }

        public function file(string $key, mixed $default = null): mixed
        {
            return $default;
        }

        public function boolean(?string $key = null, bool $default = false): bool
        {
            return $default;
        }

        public function validated(array|int|string|null $key = null, mixed $default = null): mixed
        {
            return $default ?? [];
        }

        public function ip(): ?string
        {
            return null;
        }

        public function integer(string $key, int $default = 0): int
        {
            return $default;
        }

        public function route(string $param, mixed $default = null): mixed
        {
            return $default;
        }

        public function routeIs(string|array ...$patterns): bool
        {
            return false;
        }

        public function method(): string
        {
            return 'GET';
        }

        public function string(string $key, string $default = ''): object
        {
            return new class($default)
            {
                public function __construct(private string $value) {}

                public function toString(): string
                {
                    return $this->value;
                }

                public function value(): string
                {
                    return $this->value;
                }
            };
        }
    }
}

namespace Illuminate\Foundation\Http {
    class FormRequest extends \Illuminate\Http\Request
    {
        public function validated(array|int|string|null $key = null, mixed $default = null): mixed
        {
            return $default ?? [];
        }
    }
}

namespace Illuminate\Database\Eloquent {
    trait SoftDeletes {}

    class Collection extends \Illuminate\Support\Collection implements \Countable {}

    class Builder
    {
        public function __call(string $method, array $parameters): mixed
        {
            return $this;
        }

        public function select(array|string ...$columns): static
        {
            return $this;
        }

        public function selectRaw(string $expression, array $bindings = []): static
        {
            return $this;
        }

        public function take(int $value): static
        {
            return $this;
        }

        public function chunk(int $count, callable $callback): bool
        {
            return true;
        }

        public function latest(?string $column = null): static
        {
            return $this;
        }

        public function whereIn(string $column, array $values): static
        {
            return $this;
        }

        public function where(mixed $column, mixed $operator = null, mixed $value = null): static
        {
            return $this;
        }

        public function orWhere(mixed $column, mixed $operator = null, mixed $value = null): static
        {
            return $this;
        }

        public function whereNotIn(string $column, array $values): static
        {
            return $this;
        }

        public function exists(): bool
        {
            return false;
        }

        public function value(string $column): mixed
        {
            return null;
        }

        public function whereNull(string $column): static
        {
            return $this;
        }

        public function lockForUpdate(): static
        {
            return $this;
        }

        public function groupBy(string ...$groups): static
        {
            return $this;
        }

        public function max(string $column): mixed
        {
            return null;
        }

        public function first(array $columns = ['*']): mixed
        {
            return null;
        }

        public function orderByRaw(string $sql, array $bindings = []): static
        {
            return $this;
        }

        public function orderByDesc(string $column): static
        {
            return $this;
        }

        public function whereDate(string $column, string $operator, mixed $value = null): static
        {
            return $this;
        }

        public function whereMonth(string $column, mixed $value): static
        {
            return $this;
        }

        public function whereYear(string $column, mixed $value): static
        {
            return $this;
        }

        public function whereKey(mixed $id): static
        {
            return $this;
        }

        public function withCount(array|string $relations): static
        {
            return $this;
        }

        public function whereHas(string $relation, ?callable $callback = null): static
        {
            return $this;
        }

        public function limit(int $value): static
        {
            return $this;
        }

        public function toBase(): \Illuminate\Database\Query\Builder
        {
            throw new \BadMethodCallException('stub');
        }

        public function sum(string $column): int|float
        {
            return 0;
        }

        public function find(mixed $id, array $columns = ['*']): mixed
        {
            return null;
        }

        public function pluck(string $column, ?string $key = null): \Illuminate\Support\Collection
        {
            return new \Illuminate\Support\Collection;
        }

        public function create(array $attributes = []): mixed
        {
            return null;
        }
    }
}

namespace Illuminate\Database\Query {
    class Builder
    {
        public function __call(string $method, array $parameters): mixed
        {
            return $this;
        }

        public function select(array|string ...$columns): static
        {
            return $this;
        }

        public function value(string $column): mixed
        {
            return null;
        }

        public function whereIn(string $column, array $values): static
        {
            return $this;
        }

        public function where(mixed $column, mixed $operator = null, mixed $value = null): static
        {
            return $this;
        }

        public function orWhere(mixed $column, mixed $operator = null, mixed $value = null): static
        {
            return $this;
        }

        public function whereNull(string $column): static
        {
            return $this;
        }

        public function lockForUpdate(): static
        {
            return $this;
        }

        public function insert(array $values): bool
        {
            return true;
        }

        public function upsert(array $values, array|string $uniqueBy, ?array $update = null): int
        {
            return 0;
        }

        public function groupBy(string ...$groups): static
        {
            return $this;
        }

        public function max(string $column): mixed
        {
            return null;
        }

        public function first(array $columns = ['*']): mixed
        {
            return null;
        }

        public function orderByRaw(string $sql, array $bindings = []): static
        {
            return $this;
        }

        public function orderByDesc(string $column): static
        {
            return $this;
        }

        public function whereDate(string $column, string $operator, mixed $value = null): static
        {
            return $this;
        }

        public function whereMonth(string $column, mixed $value): static
        {
            return $this;
        }

        public function whereYear(string $column, mixed $value): static
        {
            return $this;
        }

        public function whereKey(mixed $id): static
        {
            return $this;
        }

        public function limit(int $value): static
        {
            return $this;
        }

        public function toBase(): static
        {
            return $this;
        }

        public function sum(string $column): int|float
        {
            return 0;
        }
    }
}

namespace Illuminate\Support {
    class Collection implements \Countable
    {
        public function __call(string $method, array $parameters): mixed
        {
            return $this;
        }

        public function where(string $key, mixed $operator = null, mixed $value = null): static
        {
            return $this;
        }

        public function map(callable $callback): static
        {
            return $this;
        }

        public function count(): int
        {
            return 0;
        }

        public function sortBy(string|array|callable $callback, int $options = SORT_REGULAR, bool $descending = false): static
        {
            return $this;
        }

        public function pluck(string $value, ?string $key = null): static
        {
            return $this;
        }

        public function toArray(): array
        {
            return [];
        }

        public function first(callable|string|null $callback = null, mixed $default = null): mixed
        {
            return $default;
        }

        public function has(mixed $key): bool
        {
            return false;
        }

        public function contains(mixed $key, mixed $operator = null, mixed $value = null): bool
        {
            return false;
        }

        public function isNotEmpty(): bool
        {
            return false;
        }

        public function implode(string|callable $value, ?string $glue = null): string
        {
            return '';
        }

        public function sum(callable|string|null $callback = null): int|float
        {
            return 0;
        }
    }

    class Carbon
    {
        public static function parse(string $time = '', mixed $tz = null): static
        {
            return new static;
        }

        public function copy(): static
        {
            return $this;
        }

        public function subDays(int $days): static
        {
            return $this;
        }

        public function subHours(int $hours): static
        {
            return $this;
        }

        public function isPast(): bool
        {
            return false;
        }
    }
}

namespace Webklex\PHPIMAP\Support {
    class FlagCollection
    {
        public function toArray(): array
        {
            return [];
        }
    }
}

namespace Carbon {
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

        public function subHours(int $hours): static
        {
            return $this;
        }

        public function addDays(int $days): static
        {
            return $this;
        }

        public function addMinutes(int $minutes): static
        {
            return $this;
        }

        public function toDateString(): string
        {
            return '';
        }

        public function isPast(): bool
        {
            return false;
        }

        public function diffForHumans(mixed $other = null, mixed $syntax = null, bool $short = false, mixed $parts = 1): string
        {
            return '';
        }

        public function copy(): static
        {
            return $this;
        }

        public function format(string $format): string
        {
            return '';
        }

        public function diffInSeconds(mixed $date = null, bool $absolute = true): int
        {
            return 0;
        }
    }
}

namespace Illuminate\Database\Eloquent\Relations {
    class BelongsToMany
    {
        public function __call(string $method, array $parameters): mixed
        {
            return $this;
        }

        public function get(array $columns = ['*']): \Illuminate\Support\Collection
        {
            return new \Illuminate\Support\Collection;
        }

        public function where(string $column, mixed $operator = null, mixed $value = null): static
        {
            return $this;
        }

        public function whereIn(string $column, array $values): static
        {
            return $this;
        }

        public function whereKey(mixed $id): static
        {
            return $this;
        }

        public function whereNotIn(string $column, array $values): static
        {
            return $this;
        }

        public function pluck(string $column, ?string $key = null): \Illuminate\Support\Collection
        {
            return new \Illuminate\Support\Collection;
        }

        public function latest(?string $column = null): static
        {
            return $this;
        }

        public function take(int $value): static
        {
            return $this;
        }

        public function selectRaw(string $expression, array $bindings = []): static
        {
            return $this;
        }

        public function create(array $attributes = []): mixed
        {
            return null;
        }

        public function count(): int
        {
            return 0;
        }

        public function exists(): bool
        {
            return false;
        }

        public function value(string $column): mixed
        {
            return null;
        }

        public function whereNull(string $column): static
        {
            return $this;
        }

        public function lockForUpdate(): static
        {
            return $this;
        }

        public function max(string $column): mixed
        {
            return null;
        }

        public function first(array $columns = ['*']): mixed
        {
            return null;
        }

        public function orderByRaw(string $sql, array $bindings = []): static
        {
            return $this;
        }

        public function withTimestamps(): static
        {
            return $this;
        }

        public function withPivot(string ...$columns): static
        {
            return $this;
        }

        public function wherePivot(string $column, mixed $operator = null, mixed $value = null): static
        {
            return $this;
        }

        public function using(string $class): static
        {
            return $this;
        }

        public function allowFailures(): static
        {
            return $this;
        }

        public function find(mixed $id, array $columns = ['*']): mixed
        {
            return null;
        }

        public function attach(mixed $id, array $attributes = [], bool $touch = true): void {}

        public function syncWithoutDetaching(array $ids): array
        {
            return [];
        }

        public function sync(array $ids, bool $detaching = true): array
        {
            return [];
        }

        public function detach(mixed $ids = null): int
        {
            return 0;
        }
    }

    class HasMany
    {
        public function __call(string $method, array $parameters): mixed
        {
            return $this;
        }

        public function get(array $columns = ['*']): \Illuminate\Support\Collection
        {
            return new \Illuminate\Support\Collection;
        }

        public function where(string $column, mixed $operator = null, mixed $value = null): static
        {
            return $this;
        }

        public function whereIn(string $column, array $values): static
        {
            return $this;
        }

        public function whereKey(mixed $id): static
        {
            return $this;
        }

        public function whereNotIn(string $column, array $values): static
        {
            return $this;
        }

        public function pluck(string $column, ?string $key = null): \Illuminate\Support\Collection
        {
            return new \Illuminate\Support\Collection;
        }

        public function latest(?string $column = null): static
        {
            return $this;
        }

        public function take(int $value): static
        {
            return $this;
        }

        public function selectRaw(string $expression, array $bindings = []): static
        {
            return $this;
        }

        public function create(array $attributes = []): mixed
        {
            return null;
        }

        public function count(): int
        {
            return 0;
        }

        public function exists(): bool
        {
            return false;
        }

        public function value(string $column): mixed
        {
            return null;
        }

        public function whereNull(string $column): static
        {
            return $this;
        }

        public function lockForUpdate(): static
        {
            return $this;
        }

        public function max(string $column): mixed
        {
            return null;
        }

        public function first(array $columns = ['*']): mixed
        {
            return null;
        }

        public function orderByRaw(string $sql, array $bindings = []): static
        {
            return $this;
        }

        public function withTimestamps(): static
        {
            return $this;
        }

        public function withPivot(string ...$columns): static
        {
            return $this;
        }

        public function wherePivot(string $column, mixed $operator = null, mixed $value = null): static
        {
            return $this;
        }

        public function using(string $class): static
        {
            return $this;
        }

        public function find(mixed $id, array $columns = ['*']): mixed
        {
            return null;
        }

        public function chunk(int $count, callable $callback): bool
        {
            return true;
        }

        public function update(array $attributes): int
        {
            return 0;
        }
    }
}

namespace Webklex\PHPIMAP {
    class Client
    {
        public function connect(): void {}

        public function createFolder(string $name): mixed
        {
            return null;
        }

        public function getFolder(string $name): mixed
        {
            return null;
        }

        public function disconnect(): void {}

        public function getLastError(): ?string
        {
            return null;
        }

        public function getFolders(): \Illuminate\Support\Collection
        {
            return new \Illuminate\Support\Collection;
        }
    }

    class Attachment
    {
        public string $file_name = '';
        public ?string $disposition = null;

        public function getName(): string
        {
            return '';
        }

        public function getContent(): string
        {
            return '';
        }

        public function getId(): ?string
        {
            return null;
        }

        public function getContentType(): string
        {
            return '';
        }
    }

    class ClientManager
    {
        public function make(array $config = []): mixed
        {
            return null;
        }
    }

    class Message
    {
        public function hasFlag(string $flag): bool
        {
            return false;
        }

        public function setFlag(array $flags): void {}

        public function unsetFlag(array $flags): void {}

        public function hasAttachments(): bool
        {
            return false;
        }

        public function getAttachments(): \Illuminate\Support\Collection
        {
            return new \Illuminate\Support\Collection;
        }

        public function getHeader(): mixed
        {
            return null;
        }

        public function getTextBody(): string
        {
            return '';
        }

        public function hasHTMLBody(): bool
        {
            return false;
        }

        public function getHTMLBody(): string
        {
            return '';
        }

        public function getTo(): mixed
        {
            return null;
        }

        public function getCc(): mixed
        {
            return null;
        }

        public function getBcc(): mixed
        {
            return null;
        }

        public function getFrom(): object
        {
            return new class
            {
                public function first(): mixed
                {
                    return null;
                }
            };
        }

        public function getDate(): object
        {
            return new class
            {
                public function first(): mixed
                {
                    return null;
                }
            };
        }

        public function getUid(): int|string
        {
            return 0;
        }

        public function getSubject(): ?string
        {
            return null;
        }

        public function getRawBody(): string
        {
            return '';
        }

        public function getInternalDate(): mixed
        {
            return null;
        }

        public function getFlags(): \Webklex\PHPIMAP\Support\FlagCollection
        {
            return new \Webklex\PHPIMAP\Support\FlagCollection;
        }

        public function getMessageId(): string
        {
            return '';
        }
    }
}

namespace Webklex\PHPIMAP\Exceptions {
    class ConnectionFailedException extends \Exception {}
}

namespace Google {
    class Client
    {
        public function setAuthConfig(mixed $config): void {}

        public function setScopes(array $scopes): void {}

        public function setSubject(string $email): void {}

        public function isAccessTokenExpired(): bool
        {
            return false;
        }

        public function fetchAccessTokenWithAssertion(): array
        {
            return [];
        }

        public function getScopes(): ?array
        {
            return [];
        }

        public function addScope(string $scope): void {}
    }
}

namespace Google\Service {
    class Directory
    {
        public const ADMIN_DIRECTORY_USER_READONLY = 'admin.directory.user.readonly';
        public const ADMIN_DIRECTORY_DEVICE_CHROMEOS_READONLY = 'admin.directory.device.chromeos.readonly';
        public const ADMIN_DIRECTORY_USER = 'admin.directory.user';

        public object $users;
        public object $chromeosdevices;
        public object $groups;
        public object $orgunits;
        public object $channels;

        public function __construct(?\Google\Client $client = null)
        {
            $this->users = new class
            {
                public function listUsers(array $params = []): mixed
                {
                    return null;
                }

                public function get(string $email): mixed
                {
                    return null;
                }

                public function update(string $email, mixed $user): mixed
                {
                    return null;
                }

                public function watch(mixed $channel, array $params = []): mixed
                {
                    return null;
                }

                public function insert(mixed $user): mixed
                {
                    return null;
                }

                public function delete(string $userKey): void {}

                public function patch(string $userKey, mixed $patch): mixed
                {
                    return null;
                }
            };

            $this->chromeosdevices = new class
            {
                public function listChromeosdevices(string $customerId, array $params = []): mixed
                {
                    return null;
                }
            };

            $this->groups = new class
            {
                public function watch(mixed $channel, array $params = []): mixed
                {
                    return null;
                }
            };

            $this->orgunits = new class
            {
                public function watch(mixed $channel, array $params = []): mixed
                {
                    return null;
                }
            };

            $this->channels = new class
            {
                public function stop(mixed $channel): void {}
            };
        }
    }

    class ChromeManagement
    {
        public function __construct(?\Google\Client $client = null) {}
    }
}

namespace Google\Service\Directory {
    class User
    {
        public function __construct(array $data = []) {}

        public function setName(mixed $name): void {}

        public function setPrimaryEmail(string $email): void {}

        public function setPassword(string $password): void {}

        public function setChangePasswordAtNextLogin(bool $value): void {}

        public function setOrgUnitPath(string $path): void {}

        public function setSuspended(bool $value): void {}

        public function getId(): string
        {
            return '';
        }
    }

    class UserName
    {
        public function __call(string $method, array $parameters): mixed
        {
            return null;
        }
    }

    class Channel
    {
        public function __call(string $method, array $parameters): mixed
        {
            return null;
        }
    }
}

namespace App\Http\Controllers {
    function asset(string $path, ?bool $secure = null): string
    {
        return $path;
    }
}

namespace App\Misc {
    function eventy(): object
    {
        return new class
        {
            public function filter(string $hook, mixed $value, mixed ...$args): mixed
            {
                return $value;
            }
        };
    }
}

namespace App\Widgets\Dashboard {
    function e(mixed $value): string
    {
        return (string) $value;
    }
}

namespace App\Services {
    function url(string $path = ''): string
    {
        return $path;
    }
}

namespace Modules\Action1\Services {
    class Action1Service
    {
        public function listDevices(int $clientId, string $apiKey): array
        {
            return [];
        }
    }
}

namespace Illuminate\Database\Eloquent\Factories {
    abstract class Factory
    {
        /** @var object */
        protected $faker;

        public function __construct()
        {
            $this->faker = new class
            {
                public string $slug = 'stub-slug';

                public function numberBetween(int $min = 0, int $max = 100): int
                {
                    return $min;
                }
            };
        }
    }
}

namespace Illuminate\Contracts\Auth {
    interface MustVerifyEmail {}
}

namespace Illuminate\Notifications {
    trait Notifiable {}
}

namespace Illuminate\Foundation\Auth {
    class User extends \Illuminate\Database\Eloquent\Model {}
}

namespace Lab404\Impersonate\Models {
    trait Impersonate {}
}

namespace Illuminate\Database\Eloquent {
    class Model
    {
        public function __call(string $method, array $parameters): mixed
        {
            return null;
        }

        public static function __callStatic(string $method, array $parameters): mixed
        {
            return null;
        }
    }
}

namespace Illuminate\Database {
    class MySqlConnection extends Connection
    {
        public static function __callStatic(string $method, array $parameters): mixed
        {
            return null;
        }
    }
}

namespace Illuminate\Events {
    class Dispatcher
    {
        public static function macro(string $name, mixed $macro): void {}

        public static function mixin(mixed $mixin, bool $replace = true): void {}

        public static function hasMacro(string $name): bool
        {
            return false;
        }

        public static function flushMacros(): void {}
    }
}

namespace Illuminate\Filesystem {
    class Filesystem
    {
        public static function macro(string $name, mixed $macro): void {}

        public static function mixin(mixed $mixin, bool $replace = true): void {}

        public static function hasMacro(string $name): bool
        {
            return false;
        }

        public static function flushMacros(): void {}
    }
}

namespace Illuminate\Translation {
    class Translator
    {
        public static function macro(string $name, mixed $macro): void {}

        public static function mixin(mixed $mixin, bool $replace = true): void {}

        public static function hasMacro(string $name): bool
        {
            return false;
        }

        public static function flushMacros(): void {}
    }
}

namespace Illuminate\Http\Client {
    class Factory
    {
        public function __call(string $method, array $parameters): mixed
        {
            return null;
        }

        public static function __callStatic(string $method, array $parameters): mixed
        {
            return null;
        }

        public function sequence(array $responses = []): object
        {
            return new class {};
        }

        public function fake(mixed $callback = null): static
        {
            return $this;
        }

        public function fakeSequence(?string $url = null): object
        {
            return new class {};
        }

        public function stubUrl(string $url, mixed $callback): static
        {
            return $this;
        }

        public function preventStrayRequests(bool $prevent = true): static
        {
            return $this;
        }

        public function preventingStrayRequests(): bool
        {
            return false;
        }

        public function allowStrayRequests(array|string|null $only = null): static
        {
            return $this;
        }

        public function record(): static
        {
            return $this;
        }

        public function recorded(?callable $callback = null): array
        {
            return [];
        }
    }
}
