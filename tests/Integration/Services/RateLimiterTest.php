<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Services\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * RateLimiter Integration Tests
 *
 * Tests API quota management service used for external API calls.
 * This prevents quota exhaustion when calling Google Admin, Action1,
 * Helcim, and other external services.
 *
 * Critical for:
 * - API cost management
 * - Preventing account suspension
 * - Fair resource distribution
 */
#[Group('integration')]
#[Group('services')]
#[Group('rate-limiting')]
class RateLimiterTest extends TestCase
{
    use RefreshDatabase;

    private RateLimiter $limiter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createRequiredTables();
        $this->limiter = app(RateLimiter::class);
    }

    protected function tearDown(): void
    {
        // Clean up cache keys used in tests
        Cache::flush();
        parent::tearDown();
    }

    private function createRequiredTables(): void
    {
        Schema::dropIfExists('api_rate_limit_tracking');

        Schema::create('api_rate_limit_tracking', function ($table) {
            $table->id();
            $table->string('key')->unique();
            $table->integer('attempts')->default(0);
            $table->timestamp('reset_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Test can execute callback within rate limit.
     */
    public function test_executes_callback_within_limit(): void
    {
        $key = 'test:rate_limit_1';
        $result = $this->limiter->attempt($key, 10, 60, fn () => 'success');

        $this->assertEquals('success', $result);
    }

    /**
     * Test callback result is returned.
     */
    public function test_returns_callback_result(): void
    {
        $key = 'test:rate_limit_2';
        $result = $this->limiter->attempt($key, 10, 60, fn () => ['data' => 'value']);

        $this->assertEquals(['data' => 'value'], $result);
    }

    /**
     * Test increments attempt counter.
     */
    public function test_increments_attempt_counter(): void
    {
        $key = 'test:rate_limit_3';

        // First attempt
        $this->limiter->attempt($key, 10, 60, fn () => true);
        $this->assertEquals(9, $this->limiter->remaining($key, 10));

        // Second attempt
        $this->limiter->attempt($key, 10, 60, fn () => true);
        $this->assertEquals(8, $this->limiter->remaining($key, 10));
    }

    /**
     * Test throws exception when limit exceeded.
     */
    public function test_throws_when_limit_exceeded(): void
    {
        $key = 'test:rate_limit_4';

        // Use up all attempts
        for ($i = 0; $i < 3; $i++) {
            $this->limiter->attempt($key, 3, 60, fn () => true);
        }

        $this->expectException(ThrottleRequestsException::class);
        $this->expectExceptionMessage('Rate limit exceeded');

        $this->limiter->attempt($key, 3, 60, fn () => true);
    }

    /**
     * Test remaining count is accurate.
     */
    public function test_remaining_count_accurate(): void
    {
        $key = 'test:rate_limit_5';

        $this->assertEquals(5, $this->limiter->remaining($key, 5));

        $this->limiter->attempt($key, 5, 60, fn () => true);
        $this->assertEquals(4, $this->limiter->remaining($key, 5));

        $this->limiter->attempt($key, 5, 60, fn () => true);
        $this->assertEquals(3, $this->limiter->remaining($key, 5));
    }

    /**
     * Test can clear rate limit.
     */
    public function test_can_clear_rate_limit(): void
    {
        $key = 'test:rate_limit_6';

        // Use some attempts
        $this->limiter->attempt($key, 5, 60, fn () => true);
        $this->limiter->attempt($key, 5, 60, fn () => true);

        $this->assertEquals(3, $this->limiter->remaining($key, 5));

        // Clear the limit
        $this->limiter->clear($key);

        // Should be back to full
        $this->assertEquals(5, $this->limiter->remaining($key, 5));
    }

    /**
     * Test different keys are isolated.
     */
    public function test_key_isolation(): void
    {
        $key1 = 'test:service_a';
        $key2 = 'test:service_b';

        $this->limiter->attempt($key1, 10, 60, fn () => true);
        $this->limiter->attempt($key1, 10, 60, fn () => true);

        // key2 should be unaffected
        $this->assertEquals(10, $this->limiter->remaining($key2, 10));
        $this->assertEquals(8, $this->limiter->remaining($key1, 10));
    }

    /**
     * Test persists to database.
     */
    public function test_persists_to_database(): void
    {
        $key = 'test:rate_limit_db';

        $this->limiter->attempt($key, 10, 60, fn () => true);

        $record = DB::table('api_rate_limit_tracking')
            ->where('key', $key)
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals(1, $record->attempts);
    }

    /**
     * Test usage stats calculation.
     */
    public function test_usage_stats_calculation(): void
    {
        $key = 'test:stats';

        // Use 3 out of 10
        for ($i = 0; $i < 3; $i++) {
            $this->limiter->attempt($key, 10, 60, fn () => true);
        }

        $stats = $this->limiter->getUsageStats([
            ['key' => $key, 'name' => 'Test Service', 'limit' => 10],
        ]);

        $this->assertCount(1, $stats);
        $this->assertEquals('Test Service', $stats[0]['name']);
        $this->assertEquals(10, $stats[0]['limit']);
        $this->assertEquals(3, $stats[0]['used']);
        $this->assertEquals(7, $stats[0]['remaining']);
        $this->assertEquals(30.0, $stats[0]['used_percent']);
        $this->assertEquals('success', $stats[0]['color']);
    }

    /**
     * Test usage stats warning color.
     */
    public function test_usage_stats_warning_color(): void
    {
        $key = 'test:warning';

        // Use 7 out of 10 (70%)
        for ($i = 0; $i < 7; $i++) {
            $this->limiter->attempt($key, 10, 60, fn () => true);
        }

        $stats = $this->limiter->getUsageStats([
            ['key' => $key, 'name' => 'Test', 'limit' => 10],
        ]);

        $this->assertEquals('warning', $stats[0]['color']);
    }

    /**
     * Test usage stats danger color.
     */
    public function test_usage_stats_danger_color(): void
    {
        $key = 'test:danger';

        // Use 9 out of 10 (90%)
        for ($i = 0; $i < 9; $i++) {
            $this->limiter->attempt($key, 10, 60, fn () => true);
        }

        $stats = $this->limiter->getUsageStats([
            ['key' => $key, 'name' => 'Test', 'limit' => 10],
        ]);

        $this->assertEquals('danger', $stats[0]['color']);
    }

    /**
     * Test multiple services in stats.
     */
    public function test_multiple_services_stats(): void
    {
        $this->limiter->attempt('test:google', 100, 3600, fn () => true);
        $this->limiter->attempt('test:action1', 50, 3600, fn () => true);
        $this->limiter->attempt('test:action1', 50, 3600, fn () => true);

        $stats = $this->limiter->getUsageStats([
            ['key' => 'test:google', 'name' => 'Google Admin', 'limit' => 100],
            ['key' => 'test:action1', 'name' => 'Action1', 'limit' => 50],
        ]);

        $this->assertCount(2, $stats);

        $google = collect($stats)->firstWhere('name', 'Google Admin');
        $action1 = collect($stats)->firstWhere('name', 'Action1');

        $this->assertEquals(1, $google['used']);
        $this->assertEquals(2, $action1['used']);
    }

    /**
     * Test reset expired clears old records.
     */
    public function test_reset_expired_clears_old_records(): void
    {
        // Insert an expired record
        DB::table('api_rate_limit_tracking')->insert([
            'key' => 'test:expired',
            'attempts' => 5,
            'reset_at' => now()->subMinute(),
        ]);

        // Insert a current record
        DB::table('api_rate_limit_tracking')->insert([
            'key' => 'test:current',
            'attempts' => 3,
            'reset_at' => now()->addMinute(),
        ]);

        $deleted = $this->limiter->resetExpired();

        $this->assertEquals(1, $deleted);

        // Verify expired is gone
        $this->assertNull(
            DB::table('api_rate_limit_tracking')
                ->where('key', 'test:expired')
                ->first()
        );

        // Verify current remains
        $this->assertNotNull(
            DB::table('api_rate_limit_tracking')
                ->where('key', 'test:current')
                ->first()
        );
    }

    /**
     * Test callback exception propagates.
     */
    public function test_callback_exception_propagates(): void
    {
        $key = 'test:exception';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test error');

        $this->limiter->attempt($key, 10, 60, function () {
            throw new \RuntimeException('Test error');
        });
    }

    /**
     * Test limit of zero always throttles.
     */
    public function test_zero_limit_always_throttles(): void
    {
        $key = 'test:zero_limit';

        $this->expectException(ThrottleRequestsException::class);

        $this->limiter->attempt($key, 0, 60, fn () => true);
    }

    /**
     * Test complex key format support.
     */
    public function test_complex_key_format(): void
    {
        $key = 'google_admin:sync:client_123:users';

        $this->limiter->attempt($key, 10, 60, fn () => true);

        $this->assertEquals(9, $this->limiter->remaining($key, 10));
    }
}
