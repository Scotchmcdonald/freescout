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

        return Cache::remember($key, $ttl, function () use ($key, $callback) {
            Log::debug("Cache miss: {$key}");

            return $callback();
        });
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
        Log::debug("Cache invalidate: {$key}");

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
        $tag = "{$domain}:{$entityType}:{$entityId}";
        Log::debug("Cache flush tag: {$tag}");

        Cache::tags([$tag])->flush();
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
        Log::debug("Cache put: {$key}");

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

        return Cache::has($key);
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
