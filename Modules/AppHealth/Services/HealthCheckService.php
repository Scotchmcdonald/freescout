<?php

declare(strict_types=1);

namespace Modules\AppHealth\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Modules\AppHealth\Contracts\HealthCheckContract;

class HealthCheckService implements HealthCheckContract
{
    public function basic(): array
    {
        $detailed = $this->detailed();

        return [
            'status' => $detailed['status'],
            'timestamp' => now()->toIso8601String(),
            'checks' => [
                'database' => $detailed['checks']['database']['status'] ?? 'unknown',
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
            DB::select('SELECT 1');

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
            $pong = Redis::connection()->command('PING');

            return [
                'status' => (string) $pong === 'PONG' ? 'ok' : 'degraded',
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
        $default = (string) config('queue.default', 'sync');

        try {
            if ($default === 'redis') {
                $queues = config('apphealth.queue_names', ['default']);
                $pending = 0;

                foreach ($queues as $queue) {
                    if (! is_string($queue) || $queue === '') {
                        continue;
                    }

                    $pending += (int) Redis::connection()->command('LLEN', ['queues:'.$queue]);
                }

                return [
                    'status' => 'ok',
                    'driver' => 'redis',
                    'pending_jobs' => $pending,
                ];
            }

            if ($default === 'database' && Schema::hasTable('jobs')) {
                return [
                    'status' => 'ok',
                    'driver' => 'database',
                    'pending_jobs' => DB::table('jobs')->count(),
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
