<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\RateLimiterService;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Tests\PureUnitTestCase;

final class TestRateLimiterService extends RateLimiterService
{
    /** @var array<string, int> */
    public array $attempts = [];

    /** @var array<string, int> */
    public array $availableAt = [];

    /** @var array<int, array{key: string, decay: int}> */
    public array $hitCalls = [];

    protected function getAttempts(string $key): int
    {
        return $this->attempts[$key] ?? 0;
    }

    protected function hit(string $key, int $decaySeconds): int
    {
        $this->hitCalls[] = ['key' => $key, 'decay' => $decaySeconds];
        $this->attempts[$key] = ($this->attempts[$key] ?? 0) + 1;

        return $this->attempts[$key];
    }

    protected function availableAt(string $key): int
    {
        return $this->availableAt[$key] ?? time();
    }
}

class RateLimiterServiceTest extends PureUnitTestCase
{
    private TestRateLimiterService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TestRateLimiterService;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_attempt_executes_callback_and_records_hit_when_under_limit(): void
    {
        $this->service->attempts['google:sync'] = 2;

        $result = $this->service->attempt('google:sync', 3, 900, fn (): string => 'ok');

        $this->assertSame('ok', $result);
        $this->assertSame([['key' => 'google:sync', 'decay' => 900]], $this->service->hitCalls);
        $this->assertSame(3, $this->service->attempts['google:sync']);
    }

    public function test_attempt_throws_with_retry_after_when_limit_is_reached(): void
    {
        $this->service->attempts['google:sync'] = 3;
        $this->service->availableAt['google:sync'] = time() + 45;

        try {
            $this->service->attempt('google:sync', 3, 900, fn (): string => 'never');
            $this->fail('Expected throttle exception was not thrown.');
        } catch (ThrottleRequestsException $exception) {
            $this->assertStringContainsString('Rate limit exceeded for key: google:sync.', $exception->getMessage());
            $this->assertStringContainsString('Try again in', $exception->getMessage());
        }

        $this->assertSame([], $this->service->hitCalls);
    }

    public function test_remaining_never_returns_negative_values(): void
    {
        $this->service->attempts['mailchimp:push'] = 7;

        $remaining = $this->service->remaining('mailchimp:push', 5);

        $this->assertSame(0, $remaining);
    }

    public function test_get_usage_stats_computes_percentages_colors_and_reset_metadata(): void
    {
        Carbon::setTestNow('2026-03-24 12:00:00');

        $now = time();
        $this->service->attempts = [
            'service:healthy' => 2,
            'service:warning' => 7,
            'service:danger' => 9,
            'service:expired' => 0,
        ];
        $this->service->availableAt = [
            'service:healthy' => $now + 3600,
            'service:warning' => $now + 1800,
            'service:danger' => $now + 60,
            'service:expired' => $now - 10,
        ];

        $stats = $this->service->getUsageStats([
            ['key' => 'service:healthy', 'limit' => 10, 'name' => 'Healthy'],
            ['key' => 'service:warning', 'limit' => 10, 'name' => 'Warning'],
            ['key' => 'service:danger', 'limit' => 10, 'name' => 'Danger'],
            ['key' => 'service:expired', 'limit' => 0, 'name' => 'Expired'],
        ]);

        $this->assertSame('success', $stats[0]['color']);
        $this->assertSame(20.0, $stats[0]['used_percent']);
        $this->assertGreaterThan(3500, $stats[0]['reset_in_seconds']);

        $this->assertSame('warning', $stats[1]['color']);
        $this->assertSame(70.0, $stats[1]['used_percent']);

        $this->assertSame('danger', $stats[2]['color']);
        $this->assertSame(90.0, $stats[2]['used_percent']);

        $this->assertSame('success', $stats[3]['color']);
        $this->assertSame(0, $stats[3]['used_percent']);
        $this->assertSame('Now', $stats[3]['reset_in_human']);
        $this->assertSame(0, $stats[3]['remaining']);
    }
}
