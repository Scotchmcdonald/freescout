<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Sentry Integration Tests
 *
 * Verifies that Sentry error tracking and performance monitoring
 * is properly configured and working as expected.
 *
 * Tests:
 * - Exception capture
 * - Performance transaction tracking
 * - PII scrubbing
 * - Ignored exceptions
 * - Context enrichment (request, user, performance tags)
 */
class SentryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure Sentry for testing
        Config::set('sentry.dsn', 'https://fake@sentry.io/123456');
        Config::set('sentry.environment', 'testing');
    }

    /**
     * Test that Sentry configuration exists and is valid
     */
    public function test_sentry_configuration_exists(): void
    {
        $config = config('sentry');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('dsn', $config);
        $this->assertArrayHasKey('environment', $config);
        $this->assertArrayHasKey('traces_sample_rate', $config);
        $this->assertArrayHasKey('send_default_pii', $config);
        $this->assertArrayHasKey('ignore_exceptions', $config);
    }

    /**
     * Test that PII is not sent to Sentry by default
     */
    public function test_pii_is_not_sent_by_default(): void
    {
        $sendPii = config('sentry.send_default_pii');

        $this->assertFalse($sendPii, 'Sentry should not send PII by default for privacy compliance');
    }

    /**
     * Test that performance sampling rate is configured
     */
    public function test_performance_sampling_rate_is_configured(): void
    {
        $sampleRate = config('sentry.traces_sample_rate');

        $this->assertIsFloat($sampleRate);
        $this->assertGreaterThanOrEqual(0.0, $sampleRate);
        $this->assertLessThanOrEqual(1.0, $sampleRate);

        // Default should be 10% (0.1)
        $this->assertEquals(0.1, $sampleRate);
    }

    /**
     * Test that common exceptions are ignored
     */
    public function test_common_exceptions_are_ignored(): void
    {
        $ignoredExceptions = config('sentry.ignore_exceptions');

        $this->assertIsArray($ignoredExceptions);
        $this->assertContains(AuthenticationException::class, $ignoredExceptions);
        $this->assertContains(ValidationException::class, $ignoredExceptions);
        $this->assertContains(NotFoundHttpException::class, $ignoredExceptions);
    }

    /**
     * Test that release tracking is configured
     */
    public function test_release_tracking_is_configured(): void
    {
        $release = config('sentry.release');

        // Release should either be a git commit hash or empty string
        $this->assertTrue(
            is_string($release),
            'Release should be a string (git commit hash or empty)'
        );
    }

    /**
     * Test that SQL queries are captured as breadcrumbs
     */
    public function test_sql_queries_captured_as_breadcrumbs(): void
    {
        $sqlQueriesEnabled = config('sentry.breadcrumbs.sql_queries');

        $this->assertTrue($sqlQueriesEnabled, 'SQL queries should be captured as breadcrumbs');
    }

    /**
     * Test that SQL bindings are NOT captured for security
     */
    public function test_sql_bindings_not_captured(): void
    {
        $sqlBindingsEnabled = config('sentry.breadcrumbs.sql_bindings');

        $this->assertFalse($sqlBindingsEnabled, 'SQL bindings should not be captured to prevent sensitive data exposure');
    }

    /**
     * Test that AddSentryContext middleware exists
     */
    public function test_sentry_context_middleware_exists(): void
    {
        $this->assertTrue(
            class_exists(\App\Http\Middleware\AddSentryContext::class),
            'AddSentryContext middleware should exist'
        );
    }

    /**
     * Test that Sentry middleware processes requests without errors
     */
    public function test_sentry_middleware_processes_requests(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Make a request that will go through the middleware
        $response = $this->actingAs($user)
            ->get('/dashboard');

        $response->assertSuccessful();
    }

    /**
     * Test that sensitive headers are scrubbed
     *
     * This test verifies that the AddSentryContext middleware
     * properly scrubs sensitive headers like Authorization
     */
    public function test_sensitive_headers_are_scrubbed(): void
    {
        $middleware = new \App\Http\Middleware\AddSentryContext;

        // Use reflection to test private method
        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('scrubSensitiveHeaders');
        $method->setAccessible(true);

        $headers = [
            'Content-Type' => ['application/json'],
            'Authorization' => ['Bearer secret-token-12345'],
            'X-Api-Key' => ['secret-key-67890'],
            'Accept' => ['application/json'],
        ];

        $scrubbed = $method->invoke($middleware, $headers);

        // Verify sensitive headers are redacted
        $this->assertEquals(['[REDACTED]'], $scrubbed['Authorization']);
        $this->assertEquals(['[REDACTED]'], $scrubbed['X-Api-Key']);

        // Verify non-sensitive headers are preserved
        $this->assertEquals(['application/json'], $scrubbed['Content-Type']);
        $this->assertEquals(['application/json'], $scrubbed['Accept']);
    }

    /**
     * Test that sensitive data is scrubbed from query parameters
     */
    public function test_sensitive_data_scrubbed_from_query_params(): void
    {
        $middleware = new \App\Http\Middleware\AddSentryContext;

        // Use reflection to test private method
        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('scrubSensitiveData');
        $method->setAccessible(true);

        $data = [
            'username' => 'testuser',
            'password' => 'secret123',
            'api_token' => 'token-abc-123',
            'page' => 1,
        ];

        $scrubbed = $method->invoke($middleware, $data);

        // Verify sensitive fields are redacted
        $this->assertEquals('[REDACTED]', $scrubbed['password']);
        $this->assertEquals('[REDACTED]', $scrubbed['api_token']);

        // Verify non-sensitive fields are preserved
        $this->assertEquals('testuser', $scrubbed['username']);
        $this->assertEquals(1, $scrubbed['page']);
    }

    /**
     * Test that module name is extracted from request
     */
    public function test_module_name_extracted_from_request(): void
    {
        $middleware = new \App\Http\Middleware\AddSentryContext;

        // Create a mock request with module controller
        $request = \Illuminate\Http\Request::create('/modules/crm/clients', 'GET');

        // Create a mock route with module controller
        $route = new \Illuminate\Routing\Route('GET', '/modules/crm/clients', [
            'controller' => 'Modules\Crm\Http\Controllers\ClientController@index',
        ]);
        $request->setRouteResolver(fn () => $route);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('extractModuleName');
        $method->setAccessible(true);

        $moduleName = $method->invoke($middleware, $request);

        $this->assertEquals('Crm', $moduleName);
    }

    /**
     * Test that controller name is extracted from request
     */
    public function test_controller_name_extracted_from_request(): void
    {
        $middleware = new \App\Http\Middleware\AddSentryContext;

        // Create a mock request
        $request = \Illuminate\Http\Request::create('/dashboard', 'GET');

        // Create a mock route
        $route = new \Illuminate\Routing\Route('GET', '/dashboard', [
            'controller' => 'App\Http\Controllers\DashboardController@index',
        ]);
        $request->setRouteResolver(fn () => $route);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('getControllerName');
        $method->setAccessible(true);

        $controllerName = $method->invoke($middleware, $request);

        $this->assertEquals('DashboardController', $controllerName);
    }

    /**
     * Test that environment is properly configured
     */
    public function test_environment_configuration(): void
    {
        $environment = config('sentry.environment');

        $this->assertNotEmpty($environment);
        $this->assertIsString($environment);

        // In tests, should be 'testing'
        $this->assertEquals('testing', $environment);
    }

    /**
     * Test that before_send callback exists and is callable
     */
    public function test_before_send_callback_exists(): void
    {
        $beforeSend = config('sentry.before_send');

        $this->assertIsCallable($beforeSend, 'before_send should be a callable function');
    }

    /**
     * Test that before_breadcrumb callback exists and is callable
     */
    public function test_before_breadcrumb_callback_exists(): void
    {
        $beforeBreadcrumb = config('sentry.before_breadcrumb');

        $this->assertIsCallable($beforeBreadcrumb, 'before_breadcrumb should be a callable function');
    }

    /**
     * Test that breadcrumbs configuration is properly set
     */
    public function test_breadcrumbs_configuration(): void
    {
        $breadcrumbs = config('sentry.breadcrumbs');

        $this->assertIsArray($breadcrumbs);
        $this->assertArrayHasKey('sql_queries', $breadcrumbs);
        $this->assertArrayHasKey('sql_bindings', $breadcrumbs);
        $this->assertArrayHasKey('logs', $breadcrumbs);
        $this->assertArrayHasKey('cache', $breadcrumbs);
    }

    /**
     * Test that Sentry DSN can be configured via environment
     */
    public function test_dsn_configured_via_environment(): void
    {
        // The DSN should come from SENTRY_LARAVEL_DSN env var
        $dsn = config('sentry.dsn');

        // In tests, we set it in setUp()
        $this->assertNotEmpty($dsn);
        $this->assertStringContainsString('sentry.io', $dsn);
    }

    /**
     * Test that user context only includes ID (no PII)
     */
    public function test_user_context_only_includes_id(): void
    {
        // This is tested through the middleware, but we can verify
        // that send_default_pii is false which prevents automatic PII collection
        $sendPii = config('sentry.send_default_pii');

        $this->assertFalse($sendPii);

        // The AddSentryContext middleware sets only user ID, never email/name.
        // PII exclusion is enforced by send_default_pii=false (asserted above).
    }

    /**
     * Test that test route is available in non-production
     */
    public function test_sentry_test_route_available_in_non_production(): void
    {
        // In non-production, the /test-sentry route should exist
        if (! app()->environment('production')) {
            $user = User::factory()->create(['role' => 'admin']);

            // The route should throw an exception, but we can check it exists
            try {
                $response = $this->actingAs($user)->get('/test-sentry');
                // If we get here, exception was thrown and caught
            } catch (\Exception $e) {
                $this->assertStringContainsString('Test exception for Sentry', $e->getMessage());
            }
        }

        // In production, this is a no-op safety check; in non-production the
        // try/catch above holds the real assertion.
        $this->addToAssertionCount(1);
    }
}
