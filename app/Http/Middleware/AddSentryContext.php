<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * AddSentryContext Middleware
 *
 * Enriches Sentry error reports with contextual information while
 * respecting privacy and avoiding PII (Personally Identifiable Information).
 *
 * Context added:
 * - Request details (URL, method, headers - sensitive tokens scrubbed)
 * - User context (ID only, no email/name for privacy)
 * - Performance tags (queue, module, controller)
 * - Custom tags for filtering in Sentry dashboard
 *
 * PII Protection:
 * - No email addresses
 * - No user names
 * - No IP addresses (unless explicitly enabled via SENTRY_SEND_DEFAULT_PII)
 * - Authorization tokens scrubbed from headers
 */
class AddSentryContext
{
    /**
     * Headers that should be scrubbed or excluded entirely
     */
    private const SENSITIVE_HEADERS = [
        'authorization',
        'cookie',
        'x-api-key',
        'x-auth-token',
        'x-csrf-token',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only configure Sentry if DSN is set
        if (! config('sentry.dsn')) {
            return $next($request);
        }

        // Configure Sentry scope with context
        \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($request) {
            $this->addRequestContext($scope, $request);
            $this->addUserContext($scope);
            $this->addPerformanceTags($scope, $request);
        });

        return $next($request);
    }

    /**
     * Add request context to Sentry scope
     *
     * Includes URL, method, and scrubbed headers
     */
    private function addRequestContext(\Sentry\State\Scope $scope, Request $request): void
    {
        // Add request URL and method
        $scope->setContext('request', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'route' => $request->route()?->getName() ?? 'unknown',
            'controller' => $this->getControllerName($request),
            'ajax' => $request->ajax(),
        ]);

        // Add scrubbed headers
        /** @var array<string, array<int, string>> $rawHeaders */
        $rawHeaders = $request->headers->all();
        $headers = $this->scrubSensitiveHeaders($rawHeaders);
        $scope->setContext('headers', $headers);

        // Add query parameters (if not sensitive)
        $queryParams = $request->query();
        if (! empty($queryParams)) {
            /** @var array<string, mixed> $queryParams */
            $scope->setContext('query_params', $this->scrubSensitiveData($queryParams));
        }
    }

    /**
     * Add user context to Sentry scope
     *
     * Only includes user ID for privacy - no email or name
     */
    private function addUserContext(\Sentry\State\Scope $scope): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user === null) {
                return;
            }

            // Only send user ID - no PII
            $scope->setUser([
                'id' => (string) $user->id,
                // Note: Not including email or username to protect PII
            ]);

            // Add user role as tag for filtering
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                $scope->setTag('user_role', 'admin');
            } else {
                $scope->setTag('user_role', 'user');
            }
        } else {
            $scope->setUser([
                'id' => 'anonymous',
            ]);
            $scope->setTag('user_role', 'guest');
        }
    }

    /**
     * Add performance and filtering tags to Sentry scope
     */
    private function addPerformanceTags(\Sentry\State\Scope $scope, Request $request): void
    {
        // Add module tag if this is a modular route
        $module = $this->extractModuleName($request);
        if ($module) {
            $scope->setTag('module', $module);
        }

        // Add controller tag
        $controller = $this->getControllerName($request);
        if ($controller) {
            $scope->setTag('controller', $controller);
        }

        // Add request type
        $scope->setTag('request_type', $request->ajax() ? 'ajax' : 'http');

        // Add queue job context if processing a job
        if (app()->runningInConsole()) {
            $scope->setTag('context', 'console');

            // Try to detect queue worker
            if (isset($_SERVER['argv']) && is_array($_SERVER['argv']) && in_array('queue:work', $_SERVER['argv'])) {
                $scope->setTag('queue_worker', 'true');
            }
        } else {
            $scope->setTag('context', 'web');
        }
    }

    /**
     * Scrub sensitive headers from request
     *
     * @param  array<string, array<int, string>>  $headers
     * @return array<string, array<int, string>>
     */
    private function scrubSensitiveHeaders(array $headers): array
    {
        $scrubbed = [];

        foreach ($headers as $key => $values) {
            $lowerKey = strtolower($key);

            if (in_array($lowerKey, self::SENSITIVE_HEADERS)) {
                $scrubbed[$key] = ['[REDACTED]'];
            } else {
                $scrubbed[$key] = $values;
            }
        }

        return $scrubbed;
    }

    /**
     * Scrub sensitive data from arrays (passwords, tokens, etc.)
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function scrubSensitiveData(array $data): array
    {
        $scrubbed = [];

        foreach ($data as $key => $value) {
            // Check if key looks sensitive
            if (preg_match('/password|token|secret|api_key|credential/i', (string) $key)) {
                $scrubbed[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                /** @var array<string, mixed> $value */
                $scrubbed[$key] = $this->scrubSensitiveData($value);
            } else {
                $scrubbed[$key] = $value;
            }
        }

        return $scrubbed;
    }

    /**
     * Extract module name from request
     */
    private function extractModuleName(Request $request): ?string
    {
        $route = $request->route();
        if (! $route) {
            return null;
        }

        /** @var array<string, mixed> $action */
        $action = $route->getAction();

        // Try to get module from controller namespace
        if (isset($action['controller'])) {
            $controller = $action['controller'];

            if (is_string($controller) && preg_match('/Modules\\\\([^\\\\]+)/', $controller, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Get controller name from request
     */
    private function getControllerName(Request $request): ?string
    {
        $route = $request->route();
        if (! $route) {
            return null;
        }

        /** @var array<string, mixed> $action */
        $action = $route->getAction();

        if (isset($action['controller'])) {
            $controller = $action['controller'];

            if (is_string($controller)) {
                // Extract just the controller class name
                $parts = explode('@', $controller);
                $class = $parts[0];

                return class_basename($class);
            }
        }

        return null;
    }
}
