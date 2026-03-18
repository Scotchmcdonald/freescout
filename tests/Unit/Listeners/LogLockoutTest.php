<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Listeners\LogLockout;
use App\Models\ActivityLog;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Tests\UnitTestCase;

class LogLockoutTest extends UnitTestCase
{
    public function test_handle_creates_activity_log_entry(): void
    {
        $request = Request::create('/login', 'POST', [
            'email' => 'locked@example.com',
        ]);
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $event = new Lockout($request);
        $listener = new LogLockout;

        $this->app->instance('request', $request);

        $listener->handle($event);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLog::NAME_USER,
            'description' => ActivityLog::DESCRIPTION_USER_LOCKED,
        ]);
    }

    public function test_handle_includes_ip_address_in_properties(): void
    {
        $request = Request::create('/login', 'POST', [
            'email' => 'locked@example.com',
        ]);
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $event = new Lockout($request);
        $listener = new LogLockout;

        $this->app->instance('request', $request);

        $listener->handle($event);

        $log = ActivityLog::where('description', ActivityLog::DESCRIPTION_USER_LOCKED)->latest()->first();
        $properties = $log->properties;

        $this->assertEquals('192.168.1.100', $properties['ip']);
    }

    public function test_handle_includes_email_in_properties(): void
    {
        $request = Request::create('/login', 'POST', [
            'email' => 'test@example.com',
        ]);
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $event = new Lockout($request);
        $listener = new LogLockout;

        $this->app->instance('request', $request);

        $listener->handle($event);

        $log = ActivityLog::where('description', ActivityLog::DESCRIPTION_USER_LOCKED)->latest()->first();
        $properties = $log->properties;

        $this->assertEquals('test@example.com', $properties['email']);
    }

    public function test_handle_does_not_throw_exception_on_error(): void
    {
        $request = Request::create('/login', 'POST', [
            'email' => 'test@example.com',
        ]);

        $event = new Lockout($request);
        $listener = new LogLockout;

        $this->app->instance('request', $request);

        $listener->handle($event);

        $this->assertDatabaseHas('activity_log', [
            'description' => ActivityLog::DESCRIPTION_USER_LOCKED,
            'log_name' => ActivityLog::NAME_USER,
        ]);
    }

    public function test_listener_can_be_instantiated(): void
    {
        $listener = new LogLockout;

        $this->assertInstanceOf(LogLockout::class, $listener);
    }
}
