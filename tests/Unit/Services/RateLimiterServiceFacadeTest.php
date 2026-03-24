<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\RateLimiterService;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Tests\PureUnitTestCase;

class RateLimiterServiceFacadeTest extends PureUnitTestCase
{
    private ?Container $previousContainer = null;

    private mixed $previousFacadeApplication = null;

    private object $cacheStore;

    private object $dbClient;

    private RateLimiterService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();

        $app = new Application(getcwd());
        Container::setInstance($app);
        Facade::setFacadeApplication($app);

        $this->cacheStore = new class
        {
            /** @var array<int, string> */
            public array $forgotten = [];

            public function forget(string $key): void
            {
                $this->forgotten[] = $key;
            }
        };
        Cache::swap($this->cacheStore);

        $this->dbClient = new class
        {
            public string $lastTable = '';

            /** @var array<int, array{column: string, operator: string, value: mixed}> */
            public array $whereCalls = [];

            public int $deleteCount = 1;

            public function table(string $table): self
            {
                $this->lastTable = $table;

                return $this;
            }

            public function where(string $column, mixed $operator = null, mixed $value = null): self
            {
                if ($value === null) {
                    $value = $operator;
                    $operator = '=';
                }

                $this->whereCalls[] = [
                    'column' => $column,
                    'operator' => (string) $operator,
                    'value' => $value,
                ];

                return $this;
            }

            public function delete(): int
            {
                return $this->deleteCount;
            }
        };
        DB::swap($this->dbClient);

        $this->service = new RateLimiterService;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    public function test_clear_forgets_cache_keys_and_deletes_tracking_row(): void
    {
        $this->service->clear('google:sync');

        $this->assertSame(['google:sync', 'google:sync:timer'], $this->cacheStore->forgotten);
        $this->assertSame('api_rate_limit_tracking', $this->dbClient->lastTable);
        $this->assertSame('key', $this->dbClient->whereCalls[0]['column']);
        $this->assertSame('google:sync', $this->dbClient->whereCalls[0]['value']);
    }

    public function test_reset_expired_deletes_rows_with_reset_at_less_than_now(): void
    {
        Carbon::setTestNow('2026-03-24 14:00:00');
        $this->dbClient->deleteCount = 7;

        $deleted = $this->service->resetExpired();

        $this->assertSame(7, $deleted);
        $this->assertSame('api_rate_limit_tracking', $this->dbClient->lastTable);
        $this->assertSame('reset_at', $this->dbClient->whereCalls[0]['column']);
        $this->assertSame('<', $this->dbClient->whereCalls[0]['operator']);
    }
}
