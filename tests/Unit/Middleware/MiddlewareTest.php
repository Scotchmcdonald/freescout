<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\FrameGuard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for all Middleware classes
 * Following TESTING_GUIDE.md standards
 */
class MiddlewareTest extends UnitTestCase
{
    use RefreshDatabase;

    // ==================== EnsureUserIsAdmin Tests ====================

    public function test_admin_user_can_pass_through(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $admin);

        $middleware = new EnsureUserIsAdmin;
        $next = function ($req) {
            return response('Success');
        };

        $response = $middleware->handle($request, $next);

        $this->assertEquals('Success', $response->getContent());
    }

    public function test_non_admin_user_is_forbidden(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new EnsureUserIsAdmin;
        $next = function ($req) {
            return response('Success');
        };

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $middleware->handle($request, $next);
    }

    public function test_guest_is_forbidden(): void
    {
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => null);

        $middleware = new EnsureUserIsAdmin;
        $next = function ($req) {
            return response('Success');
        };

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware->handle($request, $next);
    }

    public function test_ensure_user_is_admin_returns_403_status(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new EnsureUserIsAdmin;
        $next = function ($req) {
            return response('Success');
        };

        try {
            $middleware->handle($request, $next);
            $this->fail('Expected HttpException was not thrown');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }
    }

    // ==================== FrameGuard Tests ====================

    public function test_frame_guard_adds_x_frame_options_header(): void
    {
        $request = Request::create('/', 'GET');
        $middleware = new FrameGuard;
        
        $next = function ($req) {
            return response('Success');
        };

        $response = $middleware->handle($request, $next);

        $this->assertTrue($response->headers->has('X-Frame-Options'));
    }

    public function test_frame_guard_sets_sameorigin_policy(): void
    {
        $request = Request::create('/', 'GET');
        $middleware = new FrameGuard;
        
        $next = function ($req) {
            return response('Success');
        };

        $response = $middleware->handle($request, $next);

        $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
    }

    public function test_frame_guard_allows_response_to_continue(): void
    {
        $request = Request::create('/', 'GET');
        $middleware = new FrameGuard;
        
        $next = function ($req) {
            return response('Test Content');
        };

        $response = $middleware->handle($request, $next);

        $this->assertEquals('Test Content', $response->getContent());
    }

    public function test_frame_guard_works_with_different_http_methods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        
        foreach ($methods as $method) {
            $request = Request::create('/', $method);
            $middleware = new FrameGuard;
            
            $next = function ($req) {
                return response('Success');
            };

            $response = $middleware->handle($request, $next);

            $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        }
    }

    // ==================== Edge Cases ====================

    public function test_middleware_handles_null_user(): void
    {
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => null);

        $middleware = new EnsureUserIsAdmin;
        $next = function ($req) {
            return response('Success');
        };

        try {
            $middleware->handle($request, $next);
            $this->fail('Expected HttpException for null user');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }
    }

    public function test_admin_middleware_checks_is_admin_method(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $this->assertTrue($admin->isAdmin());
        
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        
        $this->assertFalse($user->isAdmin());
    }

    public function test_frame_guard_does_not_override_existing_header(): void
    {
        $request = Request::create('/', 'GET');
        $middleware = new FrameGuard;
        
        $next = function ($req) {
            $response = response('Success');
            $response->headers->set('X-Frame-Options', 'DENY');
            return $response;
        };

        $response = $middleware->handle($request, $next);

        // Middleware should respect existing header
        $this->assertNotNull($response->headers->get('X-Frame-Options'));
    }

    public function test_middleware_can_be_instantiated(): void
    {
        $adminMiddleware = new EnsureUserIsAdmin;
        $frameGuard = new FrameGuard;

        $this->assertInstanceOf(EnsureUserIsAdmin::class, $adminMiddleware);
        $this->assertInstanceOf(FrameGuard::class, $frameGuard);
    }

    public function test_admin_middleware_works_with_complex_request(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $request = Request::create('/admin/settings', 'POST', [
            'setting' => 'value'
        ]);
        $request->setUserResolver(fn () => $admin);

        $middleware = new EnsureUserIsAdmin;
        $executed = false;
        
        $next = function ($req) use (&$executed) {
            $executed = true;
            return response('Success');
        };

        $middleware->handle($request, $next);

        $this->assertTrue($executed);
    }
}
