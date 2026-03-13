<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Centralized caching service with standardized key naming and TTL management.
 *
 * Implements the caching strategy from SYSTEM_ARCHITECTURE.md Section 14.2
 */
class CacheService
{
    /**
     * Cache TTL constants (in seconds)
     */
    public const TTL_USER_PERMISSIONS = 86400;     // 24 hours
    public const TTL_CLIENT_ENTITLEMENTS = 300;    // 5 minutes
    public const TTL_CREDIT_BALANCE = 60;          // 1 minute
    public const TTL_ASSET_COUNT = 300;            // 5 minutes
    public const TTL_RATE_LIMITER = 3600;          // 1 hour
    public const TTL_QUERY_RESULTS = 300;          // 5 minutes
    public const TTL_HOT_DATA = 60;                // 1 minute

    private ?bool $supportsTags = null;

    /**
     * Remember a value with standardized key naming.
     *
     * @param  string  $domain  Domain/module name (e.g., 'billing', 'crm', 'asset')
     * @param  string  $entityType  Entity type (e.g., 'entitlement', 'client', 'invoice')
     * @param  int|string  $entityId  Entity ID
     * @param  string|null  $attribute  Optional attribute name (e.g., 'current', 'balance')
     * @param  int  $ttl  Time-to-live in seconds
     * @param  callable  $callback  Callback to fetch value on cache miss
     * @return mixed Cached or freshly fetched value
     */
    public function remember(
        string $domain,
        string $entityType,
        int|string $entityId,
        ?string $attribute,
        int $ttl,
        callable $callback
    ): mixed {
        $key = $this->buildKey($domain, $entityType, $entityId, $attribute);
        $tag = $this->buildTag($domain, $entityType, $entityId);

        if ($this->supportsTags()) {
            $value = Cache::tags([$tag])->remember($key, $ttl, function () use ($key, $callback) {
                Log::debug("Cache miss: {$key}");

                return $callback();
            });

            // Keep a direct key copy for stores/tests that check untagged keys.
            Cache::put($key, $value, $ttl);
            $this->registerEntityKey($tag, $key);

            return $value;
        }

        $value = Cache::remember($key, $ttl, function () use ($key, $callback) {
            Log::debug("Cache miss: {$key}");

            return $callback();
        });

        $this->registerEntityKey($tag, $key);

        return $value;
    }

    /**
     * Forget (invalidate) a cached value.
     *
     * @param  string  $domain  Domain/module name
     * @param  string  $entityType  Entity type
     * @param  int|string  $entityId  Entity ID
     * @param  string|null  $attribute  Optional attribute name
     * @return bool True if key was forgotten
     */
    public function forget(
        string $domain,
        string $entityType,
        int|string $entityId,
        ?string $attribute = null
    ): bool {
        $key = $this->buildKey($domain, $entityType, $entityId, $attribute);
        $tag = $this->buildTag($domain, $entityType, $entityId);
        Log::debug("Cache invalidate: {$key}");

        $this->unregisterEntityKey($tag, $key);

        if ($this->supportsTags()) {
            $taggedForgotten = Cache::tags([$tag])->forget($key);
            $directForgotten = Cache::forget($key);

            return $taggedForgotten || $directForgotten;
        }

        return Cache::forget($key);
    }

    /**
     * Flush all caches for an entity (using tags).
     *
     * @param  string  $domain  Domain/module name
     * @param  string  $entityType  Entity type
     * @param  int|string  $entityId  Entity ID
     */
    public function flushEntity(
        string $domain,
        string $entityType,
        int|string $entityId
    ): void {
        $tag = $this->buildTag($domain, $entityType, $entityId);
        $registryKey = $this->buildRegistryKey($tag);
        $keys = Cache::get($registryKey, []);
        Log::debug("Cache flush tag: {$tag}");

        if ($this->supportsTags()) {
            if (is_array($keys)) {
                foreach ($keys as $key) {
                    if (is_string($key)) {
                        // Keep mirrored untagged key space in sync with tag flushes.
                        Cache::forget($key);
                    }
                }
            }

            Cache::tags([$tag])->flush();
            Cache::forget($registryKey);

            return;
        }

        if (is_array($keys)) {
            foreach ($keys as $key) {
                if (is_string($key)) {
                    Cache::forget($key);
                }
            }
        }

        Cache::forget($registryKey);
    }

    /**
     * Put a value in cache with explicit TTL.
     *
     * @param  string  $domain  Domain/module name
     * @param  string  $entityType  Entity type
     * @param  int|string  $entityId  Entity ID
     * @param  string|null  $attribute  Optional attribute name
     * @param  mixed  $value  Value to cache
     * @param  int  $ttl  Time-to-live in seconds
     * @return bool True if value was stored
     */
    public function put(
        string $domain,
        string $entityType,
        int|string $entityId,
        ?string $attribute,
        mixed $value,
        int $ttl
    ): bool {
        $key = $this->buildKey($domain, $entityType, $entityId, $attribute);
        $tag = $this->buildTag($domain, $entityType, $entityId);
        Log::debug("Cache put: {$key}");

        $this->registerEntityKey($tag, $key);

        if ($this->supportsTags()) {
            $taggedStored = Cache::tags([$tag])->put($key, $value, $ttl);
            $directStored = Cache::put($key, $value, $ttl);

            return $taggedStored && $directStored;
        }

        return Cache::put($key, $value, $ttl);
    }

    /**
     * Get a cached value without remembering.
     *
     * @param  string  $domain  Domain/module name
     * @param  string  $entityType  Entity type
     * @param  int|string  $entityId  Entity ID
     * @param  string|null  $attribute  Optional attribute name
     * @param  mixed  $default  Default value if key doesn't exist
     * @return mixed Cached value or default
     */
    public function get(
        string $domain,
        string $entityType,
        int|string $entityId,
        ?string $attribute = null,
        mixed $default = null
    ): mixed {
        $key = $this->buildKey($domain, $entityType, $entityId, $attribute);
        $tag = $this->buildTag($domain, $entityType, $entityId);

        if ($this->supportsTags()) {
            $value = Cache::tags([$tag])->get($key, null);

            return $value ?? Cache::get($key, $default);
        }

        return Cache::get($key, $default);
    }

    /**
     * Check if a key exists in cache.
     *
     * @param  string  $domain  Domain/module name
     * @param  string  $entityType  Entity type
     * @param  int|string  $entityId  Entity ID
     * @param  string|null  $attribute  Optional attribute name
     * @return bool True if key exists
     */
    public function has(
        string $domain,
        string $entityType,
        int|string $entityId,
        ?string $attribute = null
    ): bool {
        $key = $this->buildKey($domain, $entityType, $entityId, $attribute);
        $tag = $this->buildTag($domain, $entityType, $entityId);

        if ($this->supportsTags()) {
            return Cache::tags([$tag])->has($key) || Cache::has($key);
        }

        return Cache::has($key);
    }

    private function supportsTags(): bool
    {
        if ($this->supportsTags !== null) {
            return $this->supportsTags;
        }

        $this->supportsTags = method_exists(Cache::getStore(), 'tags');

        return $this->supportsTags;
    }

    private function buildTag(string $domain, string $entityType, int|string $entityId): string
    {
        return "{$domain}:{$entityType}:{$entityId}";
    }

    private function buildRegistryKey(string $tag): string
    {
        return "cache_registry:{$tag}";
    }

    private function registerEntityKey(string $tag, string $key): void
    {
        $registryKey = $this->buildRegistryKey($tag);
        $keys = Cache::get($registryKey, []);
        if (! is_array($keys)) {
            $keys = [];
        }

        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::forever($registryKey, $keys);
        }
    }

    private function unregisterEntityKey(string $tag, string $key): void
    {
        $registryKey = $this->buildRegistryKey($tag);
        $keys = Cache::get($registryKey, []);
        if (! is_array($keys)) {
            return;
        }

        $remaining = array_values(array_filter($keys, fn (mixed $value): bool => $value !== $key));

        if ($remaining === []) {
            Cache::forget($registryKey);

            return;
        }

        Cache::forever($registryKey, $remaining);
    }

    /**
     * Build standardized cache key.
     *
     * Format: {domain}:{entity_type}:{entity_id}:{attribute?}
     * Example: billing:entitlement:123:current
     *
     * @param  string  $domain  Domain/module name
     * @param  string  $entityType  Entity type
     * @param  int|string  $entityId  Entity ID
     * @param  string|null  $attribute  Optional attribute name
     * @return string Standardized cache key
     */
    private function buildKey(
        string $domain,
        string $entityType,
        int|string $entityId,
        ?string $attribute
    ): string {
        $parts = [$domain, $entityType, $entityId];
        if ($attribute) {
            $parts[] = $attribute;
        }

        return implode(':', $parts);
    }

    /**
     * Warm cache for multiple entities.
     *
     * @param  string  $domain  Domain/module name
     * @param  string  $entityType  Entity type
     * @param  array<int, int|string>  $entityIds  Array of entity IDs to warm
     * @param  string|null  $attribute  Optional attribute name
     * @param  int  $ttl  Time-to-live in seconds
     * @param  callable  $callback  Callback that accepts entity ID and returns value
     * @return int Number of entities warmed
     */
    public function warmMultiple(
        string $domain,
        string $entityType,
        array $entityIds,
        ?string $attribute,
        int $ttl,
        callable $callback
    ): int {
        $warmed = 0;

        foreach ($entityIds as $entityId) {
            try {
                $this->remember($domain, $entityType, $entityId, $attribute, $ttl, function () use ($callback, $entityId) {
                    return $callback($entityId);
                });
                $warmed++;
            } catch (\Exception $e) {
                Log::warning("Failed to warm cache for {$domain}:{$entityType}:{$entityId}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $warmed;
    }

    /**
     * Clear all caches for a domain.
     *
     * @param  string  $domain  Domain/module name
     */
    public function flushDomain(string $domain): void
    {
        Log::info("Flushing all caches for domain: {$domain}");
        Cache::tags([$domain])->flush();
    }
}
