<?php

declare(strict_types=1);

namespace Modules\AppHealth\Services;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Modules\AppHealth\Contracts\HealthCheckContract;

class HealthCheckService implements HealthCheckContract
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly RedisFactory $redis,
        private readonly SchemaBuilder $schema
    ) {}

    public function basic(): array
    {
        $detailed = $this->detailed();
        $status = is_string($detailed['status'] ?? null) ? $detailed['status'] : 'unknown';

        $checks = is_array($detailed['checks'] ?? null) ? $detailed['checks'] : [];
        $databaseCheck = is_array($checks['database'] ?? null) ? $checks['database'] : [];
        $databaseStatus = is_string($databaseCheck['status'] ?? null) ? $databaseCheck['status'] : 'unknown';

        return [
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'checks' => [
                'database' => $databaseStatus,
            ],
        ];
    }

    public function detailed(): array
    {
        $checks = [
            'database' => $this->databaseCheck(),
            'redis' => $this->redisCheck(),
            'queue' => $this->queueBacklogCheck(),
            'storage' => $this->storageCheck(),
        ];

        $isDegraded = collect($checks)->contains(fn (array $check): bool => $check['status'] !== 'ok');

        return [
            'status' => $isDegraded ? 'degraded' : 'ok',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function databaseCheck(): array
    {
        $start = microtime(true);

        try {
            $this->db->select('SELECT 1');

            return [
                'status' => 'ok',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'failed',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
                'reason' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function redisCheck(): array
    {
        $start = microtime(true);

        try {
            $pong = $this->redis->connection()->command('PING');

            $pongValue = is_scalar($pong) ? (string) $pong : '';

            return [
                'status' => $pongValue === 'PONG' ? 'ok' : 'degraded',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'degraded',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
                'reason' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function queueBacklogCheck(): array
    {
        $configuredDefault = config('queue.default', 'sync');
        $default = is_string($configuredDefault) ? $configuredDefault : 'sync';

        try {
            if ($default === 'redis') {
                $configuredQueues = config('apphealth.queue_names', ['default']);
                $queues = is_array($configuredQueues) ? $configuredQueues : ['default'];
                $pending = 0;

                foreach ($queues as $queue) {
                    if (! is_string($queue) || $queue === '') {
                        continue;
                    }

                    $queueLength = $this->redis->connection()->command('LLEN', ['queues:'.$queue]);
                    $pending += is_numeric($queueLength) ? (int) $queueLength : 0;
                }

                return [
                    'status' => 'ok',
                    'driver' => 'redis',
                    'pending_jobs' => $pending,
                ];
            }

            if ($default === 'database' && $this->schema->hasTable('jobs')) {
                return [
                    'status' => 'ok',
                    'driver' => 'database',
                    'pending_jobs' => $this->db->table('jobs')->count(),
                ];
            }

            return [
                'status' => 'ok',
                'driver' => $default,
                'pending_jobs' => 0,
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'degraded',
                'driver' => $default,
                'pending_jobs' => null,
                'reason' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function storageCheck(): array
    {
        $writable = is_writable(storage_path());

        return [
            'status' => $writable ? 'ok' : 'failed',
            'writable' => $writable,
        ];
    }
}
