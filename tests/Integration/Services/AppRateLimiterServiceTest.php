<?php

namespace Tests\Integration;

use App\Services\RateLimiterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RateLimiterTest extends TestCase
{
    use RefreshDatabase;

    protected RateLimiterService $limiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->limiter = new RateLimiterService;
        Cache::flush();
    }

    public function test_allows_requests_within_limit(): void
    {
        $executed = false;

        $result = $this->limiter->attempt(
            key: 'test_key',
            maxAttempts: 5,
            decaySeconds: 60,
            callback: function () use (&$executed) {
                $executed = true;

                return 'success';
            }
        );

        $this->assertTrue($executed);
        $this->assertEquals('success', $result);
    }

    public function test_blocks_requests_over_limit(): void
    {
        $key = 'test_limit';
        $maxAttempts = 3;

        // Use up the limit
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->limiter->attempt($key, $maxAttempts, 60, fn () => 'ok');
        }

        // Next request should be blocked
        $this->expectException(ThrottleRequestsException::class);
        $this->limiter->attempt($key, $maxAttempts, 60, fn () => 'fail');
    }

    public function test_tracks_remaining_attempts(): void
    {
        $key = 'test_remaining';
        $maxAttempts = 5;

        // Make 2 requests
        $this->limiter->attempt($key, $maxAttempts, 60, fn () => 'ok');
        $this->limiter->attempt($key, $maxAttempts, 60, fn () => 'ok');

        $remaining = $this->limiter->remaining($key, $maxAttempts);

        $this->assertEquals(3, $remaining);
    }

    public function test_clear_resets_limit(): void
    {
        $key = 'test_clear';
        $maxAttempts = 2;

        // Use up the limit
        $this->limiter->attempt($key, $maxAttempts, 60, fn () => 'ok');
        $this->limiter->attempt($key, $maxAttempts, 60, fn () => 'ok');

        // Clear the limit
        $this->limiter->clear($key);

        // Should be able to make requests again
        $result = $this->limiter->attempt($key, $maxAttempts, 60, fn () => 'success');

        $this->assertEquals('success', $result);
    }

    public function test_different_keys_are_independent(): void
    {
        $maxAttempts = 2;

        // Use up limit for key1
        $this->limiter->attempt('key1', $maxAttempts, 60, fn () => 'ok');
        $this->limiter->attempt('key1', $maxAttempts, 60, fn () => 'ok');

        // key2 should still work
        $result = $this->limiter->attempt('key2', $maxAttempts, 60, fn () => 'success');

        $this->assertEquals('success', $result);
    }

    public function test_persists_to_database(): void
    {
        $key = 'test_persist';

        $this->limiter->attempt($key, 10, 60, fn () => 'ok');

        $this->assertDatabaseHas('api_rate_limit_tracking', [
            'key' => $key,
            'attempts' => 1,
        ]);
    }

    public function test_restores_from_database_after_cache_clear(): void
    {
        $key = 'test_restore';
        $maxAttempts = 5;

        // Make some requests
        $this->limiter->attempt($key, $maxAttempts, 60, fn () => 'ok');
        $this->limiter->attempt($key, $maxAttempts, 60, fn () => 'ok');

        // Clear cache (simulates restart)
        Cache::flush();

        // Should restore from database
        $remaining = $this->limiter->remaining($key, $maxAttempts);

        $this->assertEquals(3, $remaining);
    }
}
