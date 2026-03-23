<?php

declare(strict_types=1);

namespace Modules\AppHealth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\AppHealth\Contracts\MetricRecorderContract;
use Symfony\Component\HttpFoundation\Response;

class RecordHttpRouteMetrics
{
    public function __construct(private readonly MetricRecorderContract $metrics) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('apphealth.http_instrumentation_enabled', true)) {
            return $next($request);
        }

        $startedAt = microtime(true);
        $response = $next($request);

        $durationSeconds = max(microtime(true) - $startedAt, 0.0);
        $routeGroup = $this->resolveRouteGroup($request);
        $statusClass = $this->statusClass($response->getStatusCode());

        $labels = [
            'route_group' => $routeGroup,
            'method' => strtolower($request->method()),
            'status' => (string) $response->getStatusCode(),
            'status_class' => $statusClass,
        ];

        $this->metrics->increment('http_requests_total', 1, $labels);
        $this->metrics->recordHistogram('http_request_duration_seconds', $durationSeconds, $labels);

        return $response;
    }

    private function resolveRouteGroup(Request $request): string
    {
        $routeName = (string) optional($request->route())->getName();

        if ($routeName !== '') {
            if (str_starts_with($routeName, 'apphealth.')) {
                return 'apphealth';
            }

            if (str_contains($routeName, '.')) {
                return explode('.', $routeName)[0] ?: 'web';
            }
        }

        $path = trim($request->path(), '/');

        if ($path === '') {
            return 'web';
        }

        $first = explode('/', $path)[0] ?? 'web';

        return preg_replace('/[^a-z0-9_-]/i', '', strtolower($first)) ?: 'web';
    }

    private function statusClass(int $status): string
    {
        if ($status >= 500) {
            return '5xx';
        }

        if ($status >= 400) {
            return '4xx';
        }

        if ($status >= 300) {
            return '3xx';
        }

        return '2xx';
    }
}
