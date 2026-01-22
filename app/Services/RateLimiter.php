<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * RateLimiter - API quota management service
 * 
 * Provides rate limiting for external API calls to prevent quota exhaustion.
 * Uses Redis/Cache for fast lookups and database for persistence.
 * 
 * Usage:
 * $rateLimiter = app(RateLimiter::class);
 * 
 * $rateLimiter->attempt(
 *     key: 'google_api:client_' . $clientId,
 *     maxAttempts: 100,
 *     decaySeconds: 3600,
 *     callback: fn() => $googleService->syncUsers()
 * );
 */
class RateLimiter
{
    /**
     * Attempt to execute a callback within rate limit
     * 
     * @param string $key Unique identifier for this rate limit
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $decaySeconds Time window in seconds
     * @param callable $callback Function to execute if within limit
     * @return mixed Result from callback
     * @throws \Illuminate\Http\Exceptions\ThrottleRequestsException
     */
    public function attempt(string $key, int $maxAttempts, int $decaySeconds, callable $callback): mixed
    {
        $attempts = $this->getAttempts($key);
        
        if ($attempts >= $maxAttempts) {
            $availableAt = $this->availableAt($key);
            $retryAfter = max(0, $availableAt - time());
            
            throw new \Illuminate\Http\Exceptions\ThrottleRequestsException(
                "Rate limit exceeded for key: {$key}. Try again in {$retryAfter} seconds."
            );
        }
        
        // Execute callback
        $result = $callback();
        
        // Increment attempt counter
        $this->hit($key, $decaySeconds);
        
        return $result;
    }
    
    /**
     * Get number of remaining attempts
     * 
     * @param string $key Rate limit key
     * @param int $maxAttempts Maximum attempts allowed
     * @return int Remaining attempts
     */
    public function remaining(string $key, int $maxAttempts): int
    {
        $attempts = $this->getAttempts($key);
        return max(0, $maxAttempts - $attempts);
    }
    
    /**
     * Clear rate limit for a key
     * 
     * @param string $key Rate limit key
     * @return void
     */
    public function clear(string $key): void
    {
        Cache::forget($key);
        Cache::forget($key . ':timer');
        
        DB::table('api_rate_limit_tracking')
            ->where('key', $key)
            ->delete();
    }
    
    /**
     * Reset all expired rate limits
     * 
     * @return int Number of limits reset
     */
    public function resetExpired(): int
    {
        return DB::table('api_rate_limit_tracking')
            ->where('reset_at', '<', now())
            ->delete();
    }
    
    /**
     * Get usage statistics for all services (for dashboard)
     * 
     * @param array $services Array of [key, limit] pairs
     * @return array Usage statistics
     */
    public function getUsageStats(array $services): array
    {
        $stats = [];
        
        foreach ($services as $service) {
            $key = $service['key'];
            $limit = $service['limit'];
            $used = $this->getAttempts($key);
            $remaining = $this->remaining($key, $limit);
            $resetAt = $this->availableAt($key);
            $usedPercent = $limit > 0 ? round(($used / $limit) * 100, 1) : 0;
            
            // Determine color based on usage percentage
            $color = 'success';
            if ($usedPercent >= 90) {
                $color = 'danger';
            } elseif ($usedPercent >= 70) {
                $color = 'warning';
            }
            
            $stats[] = [
                'name' => $service['name'],
                'limit' => $limit,
                'used' => $used,
                'remaining' => $remaining,
                'used_percent' => $usedPercent,
                'color' => $color,
                'reset_at' => $resetAt,
                'reset_in_seconds' => max(0, $resetAt - time()),
                'reset_in_human' => $resetAt > time() ? \Carbon\Carbon::createFromTimestamp($resetAt)->diffForHumans() : 'Now',
            ];
        }
        
        return $stats;
    }
    
    /**
     * Get current attempt count
     */
    protected function getAttempts(string $key): int
    {
        // Try cache first
        $attempts = Cache::get($key);
        
        if ($attempts !== null) {
            return (int) $attempts;
        }
        
        // Fallback to database
        $record = DB::table('api_rate_limit_tracking')
            ->where('key', $key)
            ->where('reset_at', '>', now())
            ->first();
        
        if ($record) {
            // Restore to cache
            $ttl = now()->diffInSeconds($record->reset_at);
            Cache::put($key, $record->attempts, $ttl);
            Cache::put($key . ':timer', strtotime($record->reset_at), $ttl);
            
            return $record->attempts;
        }
        
        return 0;
    }
    
    /**
     * Increment attempt counter
     */
    protected function hit(string $key, int $decaySeconds): int
    {
        $resetAt = Cache::get($key . ':timer');
        
        if ($resetAt === null) {
            $resetAt = time() + $decaySeconds;
            Cache::put($key . ':timer', $resetAt, $decaySeconds);
        }
        
        // Increment in cache
        $attempts = Cache::increment($key);
        Cache::put($key, $attempts, $decaySeconds);
        
        // Persist to database
        DB::table('api_rate_limit_tracking')->updateOrInsert(
            ['key' => $key],
            [
                'attempts' => $attempts,
                'reset_at' => date('Y-m-d H:i:s', $resetAt),
            ]
        );
        
        return $attempts;
    }
    
    /**
     * Get timestamp when rate limit resets
     */
    protected function availableAt(string $key): int
    {
        $resetAt = Cache::get($key . ':timer');
        
        if ($resetAt !== null) {
            return $resetAt;
        }
        
        $record = DB::table('api_rate_limit_tracking')
            ->where('key', $key)
            ->first();
        
        return $record ? strtotime($record->reset_at) : time();
    }
}
