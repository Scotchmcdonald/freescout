<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Track performance metrics for HTTP requests and log slow requests.
 */
class TrackPerformanceMetrics
{
    /**
     * Threshold for slow request logging (milliseconds)
     */
    private const SLOW_REQUEST_THRESHOLD = 1000; // 1 second

    /**
     * Threshold for very slow request logging (milliseconds)
     */
    private const VERY_SLOW_REQUEST_THRESHOLD = 3000; // 3 seconds

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Continue processing the request
        $response = $next($request);

        // Calculate metrics
        $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = (memory_get_usage(true) - $startMemory) / 1024 / 1024; // Convert to MB

        // Log slow requests
        if ($duration > self::VERY_SLOW_REQUEST_THRESHOLD) {
            Log::channel('performance')->error('Very slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => round($duration, 2),
                'memory_mb' => round($memoryUsed, 2),
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'status_code' => $response->getStatusCode(),
            ]);
        } elseif ($duration > self::SLOW_REQUEST_THRESHOLD) {
            Log::channel('performance')->warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => round($duration, 2),
                'memory_mb' => round($memoryUsed, 2),
                'status_code' => $response->getStatusCode(),
            ]);
        }

        // Add performance headers for debugging (only in non-production)
        if (! app()->isProduction()) {
            $response->headers->set('X-Debug-Duration-Ms', (string) round($duration, 2));
            $response->headers->set('X-Debug-Memory-Mb', (string) round($memoryUsed, 2));
            $response->headers->set('X-Debug-Queries', (string) $this->getQueryCount());
        }

        // Track metrics in Sentry if available
        if (function_exists('\\Sentry\\startTransaction')) {
            $this->trackSentryMetrics($request, $duration, $response);
        }

        return $response;
    }

    /**
     * Get the number of database queries executed during the request.
     */
    private function getQueryCount(): int
    {
        try {
            return count(\DB::getQueryLog());
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Track performance metrics in Sentry.
     */
    private function trackSentryMetrics(Request $request, float $duration, Response $response): void
    {
        if (! function_exists('\\Sentry\\startTransaction')) {
            return;
        }

        $transactionContext = new \Sentry\Tracing\TransactionContext;
        $transactionContext->setName($request->method().' '.$request->path());
        $transactionContext->setOp('http.request');

        $transaction = \Sentry\startTransaction($transactionContext);

        // Set tags for better filtering
        \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($request, $response, $duration): void {
            $scope->setTag('http.method', $request->method());
            $scope->setTag('http.status_code', (string) $response->getStatusCode());
            $scope->setTag('route', (string) $request->route()?->getName());

            if ($request->user()) {
                $scope->setUser([
                    'id' => $request->user()->id,
                    'email' => $request->user()->email,
                ]);
            }

            // Add custom measurements
            $scope->setContext('performance', [
                'duration_ms' => round($duration, 2),
                'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'queries' => $this->getQueryCount(),
            ]);
        });

        $transaction->finish();
    }
}
