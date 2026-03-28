<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Services;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Symfony\Component\Finder\Finder;
use Throwable;

/**
 * Universally agnostic event discovery service.
 *
 * Automatically discovers every event class across the entire modular monolith
 * by scanning configured directories AND querying EventServiceProvider listener maps.
 * Results are cached with a configurable TTL to avoid filesystem I/O on every request.
 *
 * Discovery sources (in priority order):
 *   1. Active EventServiceProvider listener maps (guaranteed accuracy)
 *   2. Filesystem scan of configured `scan_paths` globs
 *   3. Previously logged event classes from the middleman_logs table
 *
 * The service merges all three sources, de-duplicates, and caches the result.
 * If a module is added or removed, the cache auto-expires and re-discovers.
 */
final class EventDiscoveryService
{
    private const CACHE_KEY = 'middleman:discovered_events';
    private const LISTENERS_KEY = 'middleman:discovered_listeners';
    private const MODULE_HASH_KEY = 'middleman:module_hash';

    private string $cacheStore;
    private int $cacheTtl;
    private CacheFactory $cache;
    private LoggerInterface $logger;

    public function __construct(CacheFactory $cache, LoggerInterface $logger)
    {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->cacheStore = (string) config('middleman.cache_store', 'redis'); // @phpstan-ignore cast.string
        $this->cacheTtl = (int) config('middleman.discovery_cache_ttl', 300); // @phpstan-ignore cast.int
    }

    /**
     * Get all discovered events with constructor signatures.
     *
     * @return array<int, array{class: string, name: string, module: string, parameters: array<int, mixed>, listener_count: int}>
     */
    public function discover(): array
    {
        if ($this->cacheTtl > 0) {
            try {
                $cached = $this->store()->get(self::CACHE_KEY);

                // If module landscape changed, invalidate
                if ($cached !== null && $this->moduleHashMatches()) {
                    /** @var array<int, array{class: string, name: string, module: string, parameters: array<int, mixed>, listener_count: int}> $cached */
                    return $cached;
                }
            } catch (Throwable) {
                // Cache failure — fall through to live discovery
            }
        }

        $events = $this->performDiscovery();

        if ($this->cacheTtl > 0) {
            try {
                $this->store()->put(self::CACHE_KEY, $events, $this->cacheTtl);
                $this->store()->put(self::MODULE_HASH_KEY, $this->computeModuleHash(), $this->cacheTtl);
            } catch (Throwable) {
                // Non-critical
            }
        }

        return $events;
    }

    /**
     * Get constructor parameters for a specific event class.
     *
     * @return array<int, array{name: string, type: string, required: bool, default: mixed, is_model: bool, model_class: string|null, is_enum: bool, enum_cases: array<int, array{name: string, value: int|string}>}>
     */
    public function getParameters(string $eventClass): array
    {
        if (! class_exists($eventClass)) {
            return [];
        }

        try {
            $ref = new ReflectionClass($eventClass);
            $constructor = $ref->getConstructor();

            if ($constructor === null) {
                return [];
            }

            return array_map(
                fn (ReflectionParameter $param): array => $this->describeParameter($param),
                $constructor->getParameters(),
            );
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Get all discovered event→listener relationships.
     *
     * @return array<string, array<int, string>>
     */
    public function getListenerMap(): array
    {
        try {
            $cached = $this->store()->get(self::LISTENERS_KEY);
            if ($cached !== null) {
                /** @var array<string, array<int, string>> $cached */
                return $cached;
            }
        } catch (Throwable) {
            // Fall through
        }

        $map = $this->extractListenerMap();

        try {
            $this->store()->put(self::LISTENERS_KEY, $map, $this->cacheTtl);
        } catch (Throwable) {
            // Non-critical
        }

        return $map;
    }

    /**
     * Force cache invalidation (called after module changes, deployments, etc.).
     */
    public function invalidateCache(): void
    {
        try {
            $store = $this->store();
            $store->forget(self::CACHE_KEY);
            $store->forget(self::LISTENERS_KEY);
            $store->forget(self::MODULE_HASH_KEY);
        } catch (Throwable) {
            // Non-critical
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Discovery Engine
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<int, array{class: string, name: string, module: string, parameters: array<int, mixed>, listener_count: int}>
     */
    private function performDiscovery(): array
    {
        $listenerMap = $this->extractListenerMap();
        $eventMap = []; // keyed by FQCN

        // Source 1: Events from listener map (highest fidelity — these are actively bound)
        foreach (array_keys($listenerMap) as $eventClass) {
            if (! is_string($eventClass) || ! class_exists($eventClass)) {
                continue;
            }

            $eventMap[$eventClass] = $this->buildEventDescriptor(
                $eventClass,
                $listenerMap[$eventClass] ?? [],
            );
        }

        // Source 2: Filesystem scan
        /** @var array<int|string, mixed> $scanPaths */
        $scanPaths = config('middleman.scan_paths', ['app/Events']);
        foreach ($scanPaths as $pattern) {
            foreach ($this->resolveGlobPaths((string) $pattern) as $dir) { // @phpstan-ignore cast.string
                if (! is_dir($dir)) {
                    continue;
                }

                foreach ($this->scanDirectory($dir) as $class) {
                    if (isset($eventMap[$class])) {
                        continue; // Already found via listener map
                    }

                    $eventMap[$class] = $this->buildEventDescriptor(
                        $class,
                        $listenerMap[$class] ?? [],
                    );
                }
            }
        }

        // Source 3: Historical — event classes from previous log entries
        try {
            $historicalClasses = \Modules\MiddleMan\Models\MiddleManLog::distinct()
                ->pluck('event_class')
                ->filter(fn (mixed $class): bool => is_string($class) && class_exists($class))
                ->all();

            foreach ($historicalClasses as $class) {
                if (isset($eventMap[$class])) {
                    continue;
                }

                $eventMap[$class] = $this->buildEventDescriptor(
                    $class,
                    $listenerMap[$class] ?? [],
                );
            }
        } catch (Throwable) {
            // DB not available — skip historical source
        }

        // Sort by class name for stable ordering
        ksort($eventMap);

        return array_values($eventMap);
    }

    /**
     * @param  string[]  $listeners
     * @return array{class: string, name: string, module: string, parameters: array<int, mixed>, listener_count: int}
     */
    private function buildEventDescriptor(string $eventClass, array $listeners): array
    {
        return [
            'class' => $eventClass,
            'name' => class_basename($eventClass),
            'module' => $this->inferModule($eventClass),
            'parameters' => $this->getParameters($eventClass),
            'listener_count' => count($listeners),
        ];
    }

    /**
     * Infer which module an event belongs to from its namespace.
     */
    private function inferModule(string $eventClass): string
    {
        // Modules\Crm\Events\... → Crm
        if (preg_match('/^Modules\\\\([^\\\\]+)\\\\/', $eventClass, $m)) {
            return $m[1];
        }

        // App\Events\... → Core
        if (str_starts_with($eventClass, 'App\\')) {
            return 'Core';
        }

        return 'Unknown';
    }

    /*
    |--------------------------------------------------------------------------
    | Listener Map Extraction
    |--------------------------------------------------------------------------
    */

    /**
     * Extract event→listener bindings from all registered EventServiceProviders.
     *
     * @return array<string, string[]>
     */
    private function extractListenerMap(): array
    {
        $map = [];

        try {
            $dispatcher = app('events');

            // Laravel's dispatcher stores listeners via getListeners()
            // We can inspect the raw listeners property via reflection
            $ref = new ReflectionClass($dispatcher);

            // Try the 'listeners' property first (Illuminate\Events\Dispatcher)
            if ($ref->hasProperty('listeners')) {
                $prop = $ref->getProperty('listeners');
                $prop->setAccessible(true);
                $rawListeners = $prop->getValue($dispatcher);

                if (is_array($rawListeners)) {
                    foreach ($rawListeners as $event => $listeners) {
                        if (! is_string($event)) {
                            continue;
                        }
                        $map[$event] = array_map(
                            fn (mixed $listener): string => $this->resolveListenerName($listener),
                            is_array($listeners) ? $listeners : [],
                        );
                    }
                }
            }
        } catch (Throwable $e) {
            $this->logger->debug('MiddleMan EventDiscovery: Could not extract listener map', [
                'error' => $e->getMessage(),
            ]);
        }

        return $map;
    }

    private function resolveListenerName(mixed $listener): string
    {
        if (is_string($listener)) {
            return $listener;
        }

        if (is_array($listener) && count($listener) === 2) {
            $class = is_object($listener[0]) ? get_class($listener[0]) : (string) $listener[0]; // @phpstan-ignore cast.string

            return $class.'@'.$listener[1];
        }

        if ($listener instanceof \Closure) {
            return '[Closure]';
        }

        return '[unknown]';
    }

    /*
    |--------------------------------------------------------------------------
    | Filesystem Scanner
    |--------------------------------------------------------------------------
    */

    /**
     * @return string[]
     */
    private function scanDirectory(string $dir): array
    {
        $classes = [];

        try {
            $finder = new Finder;
            $finder->files()->name('*.php')->in($dir)->depth('< 3');

            foreach ($finder as $file) {
                $class = $this->classFromFile($file->getRealPath());

                if ($class === null || ! class_exists($class)) {
                    continue;
                }

                try {
                    $ref = new ReflectionClass($class);
                    if ($ref->isAbstract() || $ref->isInterface() || $ref->isTrait()) {
                        continue;
                    }

                    $classes[] = $class;
                } catch (Throwable) {
                    // Skip unreflectable classes
                }
            }
        } catch (Throwable $e) {
            $this->logger->debug('MiddleMan EventDiscovery: Directory scan failed', [
                'dir' => $dir,
                'error' => $e->getMessage(),
            ]);
        }

        return $classes;
    }

    private function classFromFile(string $filePath): ?string
    {
        $contents = @file_get_contents($filePath);
        if ($contents === false) {
            return null;
        }

        $namespace = null;
        $class = null;

        if (preg_match('/namespace\s+([^;\s]+)/m', $contents, $nsMatch)) {
            $namespace = $nsMatch[1];
        }

        if (preg_match('/class\s+(\w+)/m', $contents, $clsMatch)) {
            $class = $clsMatch[1];
        }

        if ($namespace !== null && $class !== null) {
            return $namespace.'\\'.$class;
        }

        return $class;
    }

    /*
    |--------------------------------------------------------------------------
    | Parameter Introspection (Enhanced)
    |--------------------------------------------------------------------------
    */

    /**
     * @return array{name: string, type: string, required: bool, default: mixed, is_model: bool, model_class: string|null, is_enum: bool, enum_cases: array<int, array{name: string, value: int|string}>}
     */
    private function describeParameter(ReflectionParameter $param): array
    {
        $type = $param->getType();
        $typeName = 'mixed';

        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();
        }

        $desc = [
            'name' => $param->getName(),
            'type' => $typeName,
            'required' => ! $param->isOptional(),
            'default' => null,
            'is_model' => false,
            'model_class' => null,
            'is_enum' => false,
            'enum_cases' => [],
        ];

        if ($param->isDefaultValueAvailable()) {
            try {
                $desc['default'] = $param->getDefaultValue();
            } catch (Throwable) {
                $desc['default'] = null;
            }
        }

        // Detect Eloquent model parameters → renders as async-searchable Select
        if (class_exists($typeName) && is_subclass_of($typeName, \Illuminate\Database\Eloquent\Model::class)) {
            $desc['is_model'] = true;
            $desc['model_class'] = $typeName;

            // Extract searchable columns for the async search endpoint
            try {
                $instance = new $typeName;
                $desc['model_table'] = $instance->getTable();
                $desc['model_key'] = $instance->getKeyName();
            } catch (Throwable) {
                // Non-critical
            }
        }

        // Detect PHP 8.1+ backed enums → renders as dropdown with cases
        if (class_exists($typeName) && is_subclass_of($typeName, \UnitEnum::class)) {
            $desc['is_enum'] = true;
            $desc['enum_cases'] = array_map(
                fn (\UnitEnum $case): array => [
                    'name' => $case->name,
                    'value' => $case instanceof \BackedEnum ? $case->value : $case->name,
                ],
                $typeName::cases(),
            );
        }

        return $desc;
    }

    /*
    |--------------------------------------------------------------------------
    | Module Hash (Change Detection)
    |--------------------------------------------------------------------------
    */

    /**
     * Compute a hash of the current module landscape.
     * If modules are added/removed, this hash changes => cache is invalidated.
     */
    private function computeModuleHash(): string
    {
        $modules = [];

        // Check modules_statuses.json for enabled modules
        $statusFile = base_path('modules_statuses.json');
        if (file_exists($statusFile)) {
            $contents = @file_get_contents($statusFile);
            if ($contents !== false) {
                $decoded = json_decode($contents, true);
                $modules = is_array($decoded) ? array_keys($decoded) : [];
            }
        }

        // Also hash the scan_paths glob results
        /** @var array<int|string, mixed> $scanPaths */
        $scanPaths = config('middleman.scan_paths', []);
        foreach ($scanPaths as $pattern) {
            $resolved = $this->resolveGlobPaths((string) $pattern); // @phpstan-ignore cast.string
            foreach ($resolved as $dir) {
                $modules[] = $dir;
            }
        }

        sort($modules);

        return md5(implode('|', $modules));
    }

    private function moduleHashMatches(): bool
    {
        try {
            $storedHash = $this->store()->get(self::MODULE_HASH_KEY);

            return $storedHash !== null && $storedHash === $this->computeModuleHash();
        } catch (Throwable) {
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * @return string[]
     */
    private function resolveGlobPaths(string $pattern): array
    {
        $basePath = base_path($pattern);

        if (! str_contains($pattern, '*')) {
            return [$basePath];
        }

        $result = glob($basePath, GLOB_ONLYDIR);

        return $result !== false ? $result : [];
    }

    private function store(): \Illuminate\Contracts\Cache\Repository
    {
        return $this->cache->store($this->cacheStore);
    }
}
