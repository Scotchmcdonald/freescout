<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Listeners\LogFailedLogin;
use App\Models\ActivityLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Tests\UnitTestCase;

class LogFailedLoginListenerTest extends UnitTestCase
{

    public function test_listener_logs_failed_login(): void
    {
        $request = Request::create('/login', 'POST', ['email' => 'test@example.com']);
        $event = new Failed('web', null, ['email' => 'test@example.com']);
        $listener = new LogFailedLogin;

        // Set the request
        app()->instance('request', $request);

        // Clear any existing activity logs
        ActivityLog::truncate();

        $listener->handle($event);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLog::NAME_USER,
            'description' => ActivityLog::DESCRIPTION_USER_LOGIN_FAILED,
        ]);
    }

    public function test_listener_has_handle_method(): void
    {
        $listener = new LogFailedLogin;
        $this->assertTrue(method_exists($listener, 'handle'));
    }
}
