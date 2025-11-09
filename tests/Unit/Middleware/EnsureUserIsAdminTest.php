<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureUserIsAdminTest extends TestCase
{
    public function test_admin_user_can_pass_through_middleware(): void
    {
        $middleware = new EnsureUserIsAdmin();

        $user = new User();
        $user->id = 1;
        $user->role = User::ROLE_ADMIN;

        $request = Request::create('/admin/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $next = function ($request) {
            return new Response('Success');
        };

        $response = $middleware->handle($request, $next);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_non_admin_user_is_blocked(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $middleware = new EnsureUserIsAdmin();

        $user = new User();
        $user->id = 2;
        $user->role = User::ROLE_USER;

        $request = Request::create('/admin/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $next = function ($request) {
            return new Response('Should not reach here');
        };

        $middleware->handle($request, $next);
    }

    public function test_guest_user_is_blocked(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $middleware = new EnsureUserIsAdmin();

        $request = Request::create('/admin/test', 'GET');
        $request->setUserResolver(fn () => null);

        $next = function ($request) {
            return new Response('Should not reach here');
        };

        $middleware->handle($request, $next);
    }

    public function test_middleware_passes_request_to_next_handler(): void
    {
        $middleware = new EnsureUserIsAdmin();

        $user = new User();
        $user->id = 1;
        $user->role = User::ROLE_ADMIN;

        $request = Request::create('/admin/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return new Response('Next handler called');
        };

        $response = $middleware->handle($request, $next);

        $this->assertTrue($nextCalled);
        $this->assertEquals('Next handler called', $response->getContent());
    }

    public function test_middleware_validates_user_role_strictly(): void
    {
        $this->expectException(HttpException::class);

        $middleware = new EnsureUserIsAdmin();

        $user = new User();
        $user->id = 3;
        $user->role = 'not_admin'; // Invalid role

        $request = Request::create('/admin/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $next = function ($request) {
            return new Response('Should not reach here');
        };

        $middleware->handle($request, $next);
    }
}
