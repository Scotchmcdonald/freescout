<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Services;

use Illuminate\Support\Facades\Cache;

/**
 * In-memory rule engine backed by Redis/cache.
 *
 * Rules are stored as a single cached hash:
 *   { "log": ["App\\Events\\Foo", "*"], "intercept": ["App\\Events\\Bar"] }
 *
 * All reads go to cache — never to the database during dispatch.
 */
class RuleEngine
{
    private string $cacheStore;
    private string $rulesKey;
    private string $loggingKey;
    private string $interceptKey;
    private string $mutedListenersKey;

    public function __construct()
    {
        $this->cacheStore        = config('middleman.cache_store', 'redis');
        $this->rulesKey          = config('middleman.cache_keys.rules', 'middleman:rules');
        $this->loggingKey        = config('middleman.cache_keys.logging_active', 'middleman:logging_active');
        $this->interceptKey      = config('middleman.cache_keys.intercept_active', 'middleman:intercept_active');
        $this->mutedListenersKey = config('middleman.cache_keys.muted_listeners', 'middleman:muted_listeners');
    }

    /*
    |--------------------------------------------------------------------------
    | Global Switches
    |--------------------------------------------------------------------------
    */

    public function isLoggingActive(): bool
    {
        return (bool) $this->store()->get($this->loggingKey, false);
    }

    public function isInterceptActive(): bool
    {
        return (bool) $this->store()->get($this->interceptKey, false);
    }

    public function setLoggingActive(bool $active): void
    {
        $this->store()->forever($this->loggingKey, $active);
    }

    public function setInterceptActive(bool $active): void
    {
        $this->store()->forever($this->interceptKey, $active);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule CRUD
    |--------------------------------------------------------------------------
    */

    /**
     * @return array{log: string[], intercept: string[]}
     */
    public function getRules(): array
    {
        return $this->store()->get($this->rulesKey, [
            'log'       => [],
            'intercept' => [],
        ]);
    }

    public function setRules(array $rules): void
    {
        $this->store()->forever($this->rulesKey, [
            'log'       => array_values(array_unique($rules['log'] ?? [])),
            'intercept' => array_values(array_unique($rules['intercept'] ?? [])),
        ]);
    }

    public function addLogRule(string $eventClass): void
    {
        $rules = $this->getRules();
        $rules['log'][] = $eventClass;
        $this->setRules($rules);
    }

    public function removeLogRule(string $eventClass): void
    {
        $rules = $this->getRules();
        $rules['log'] = array_values(array_diff($rules['log'], [$eventClass]));
        $this->setRules($rules);
    }

    public function addInterceptRule(string $eventClass): void
    {
        $rules = $this->getRules();
        $rules['intercept'][] = $eventClass;
        $this->setRules($rules);
    }

    public function removeInterceptRule(string $eventClass): void
    {
        $rules = $this->getRules();
        $rules['intercept'] = array_values(array_diff($rules['intercept'], [$eventClass]));
        $this->setRules($rules);
    }

    public function clearAllRules(): void
    {
        $this->store()->forget($this->rulesKey);
        $this->store()->forget($this->loggingKey);
        $this->store()->forget($this->interceptKey);
        $this->store()->forget($this->mutedListenersKey);
    }

    /*
    |--------------------------------------------------------------------------
    | Listener Muting (Surgical)
    |--------------------------------------------------------------------------
    */

    /**
     * @return string[] List of muted listener class patterns
     */
    public function getMutedListeners(): array
    {
        return $this->store()->get($this->mutedListenersKey, []);
    }

    public function setMutedListeners(array $listeners): void
    {
        $this->store()->forever(
            $this->mutedListenersKey,
            array_values(array_unique($listeners)),
        );
    }

    public function addMutedListener(string $listenerClass): void
    {
        $muted = $this->getMutedListeners();
        $muted[] = $listenerClass;
        $this->setMutedListeners($muted);
    }

    public function removeMutedListener(string $listenerClass): void
    {
        $muted = $this->getMutedListeners();
        $muted = array_values(array_diff($muted, [$listenerClass]));
        $this->setMutedListeners($muted);
    }

    /**
     * Check if a specific listener name is in the muted list.
     * Supports exact match and namespace wildcard (e.g. "App\Listeners\*").
     */
    public function isListenerMuted(string $listenerName): bool
    {
        return $this->matchesAny($listenerName, $this->getMutedListeners());
    }

    /*
    |--------------------------------------------------------------------------
    | Matching
    |--------------------------------------------------------------------------
    */

    /**
     * Check if a given event class matches any rule in the specified category.
     * Supports wildcard "*" matching.
     */
    public function shouldLog(string $eventClass): bool
    {
        if (! $this->isLoggingActive()) {
            return false;
        }

        return $this->matchesAny($eventClass, $this->getRules()['log'] ?? []);
    }

    public function shouldIntercept(string $eventClass): bool
    {
        if (! $this->isInterceptActive()) {
            return false;
        }

        return $this->matchesAny($eventClass, $this->getRules()['intercept'] ?? []);
    }

    private function matchesAny(string $eventClass, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($pattern === '*') {
                return true;
            }
            if ($pattern === $eventClass) {
                return true;
            }
            // Namespace wildcard: "App\Events\*"
            if (str_ends_with($pattern, '\\*')) {
                $prefix = substr($pattern, 0, -1);
                if (str_starts_with($eventClass, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function store(): \Illuminate\Contracts\Cache\Repository
    {
        return Cache::store($this->cacheStore);
    }
}
