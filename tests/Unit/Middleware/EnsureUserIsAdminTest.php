<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\PureUnitTestCase;

class EnsureUserIsAdminTest extends PureUnitTestCase
{
    private ?Container $previousContainer = null;

    private mixed $previousFacadeApplication = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();

        $app = new Application(getcwd());
        $app->instance('log', new class
        {
            public function warning(string $message, array $context = []): void {}
        });

        Container::setInstance($app);
        Facade::setFacadeApplication($app);
        Log::swap(new class
        {
            public function warning(string $message, array $context = []): void {}
        });
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    public function test_admin_user_can_pass_through_middleware(): void
    {
        $middleware = new EnsureUserIsAdmin;
        $request = Request::create('/admin/test', 'GET');
        $request->setUserResolver(fn () => $this->makeUser(id: 1, isAdmin: true));

        $response = $middleware->handle($request, fn () => new Response('Success'));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_internal_staff_can_pass_through_middleware(): void
    {
        $middleware = new EnsureUserIsAdmin;
        $request = Request::create('/admin/test', 'GET');
        $request->setUserResolver(fn () => $this->makeUser(id: 2, role: 'user', type: 1));

        $response = $middleware->handle($request, fn () => new Response('Internal staff access'));

        $this->assertEquals('Internal staff access', $response->getContent());
    }

    public function test_user_with_admin_panel_permission_can_pass_through_middleware(): void
    {
        $middleware = new EnsureUserIsAdmin;
        $request = Request::create('/admin/test', 'GET');
        $request->setUserResolver(fn () => $this->makeUser(id: 3, role: 'user', type: 2, canAccessAdminPanel: true));

        $response = $middleware->handle($request, fn () => new Response('Permission-based access'));

        $this->assertEquals('Permission-based access', $response->getContent());
    }

    public function test_guest_user_is_blocked(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $middleware = new EnsureUserIsAdmin;
        $request = Request::create('/admin/test', 'GET');
        $request->setUserResolver(fn () => null);

        $middleware->handle($request, fn () => new Response('Should not reach here'));
    }

    public function test_non_admin_without_permission_is_blocked(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $middleware = new EnsureUserIsAdmin;
        $request = Request::create('/admin/test', 'GET');
        $request->setUserResolver(fn () => $this->makeUser(id: 4, role: 'user', type: 2));

        $middleware->handle($request, fn () => new Response('Should not reach here'));
    }

    private function makeUser(
        int $id,
        string $role = 'admin',
        int $type = 2,
        bool $isAdmin = false,
        bool $canAccessAdminPanel = false
    ): object {
        return new class($id, $role, $type, $isAdmin, $canAccessAdminPanel)
        {
            public int $id;

            public string $role;

            public int $type;

            private bool $adminFlag;

            private bool $adminPanelPermission;

            public function __construct(int $id, string $role, int $type, bool $isAdmin, bool $canAccessAdminPanel)
            {
                $this->id = $id;
                $this->role = $role;
                $this->type = $type;
                $this->adminFlag = $isAdmin;
                $this->adminPanelPermission = $canAccessAdminPanel;
            }

            public function isAdmin(): bool
            {
                return $this->adminFlag;
            }

            public function hasPermission(string $permission): bool
            {
                return $permission === 'access_admin_panel' && $this->adminPanelPermission;
            }
        };
    }
}
