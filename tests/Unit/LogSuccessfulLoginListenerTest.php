<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Listeners\LogSuccessfulLogin;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogSuccessfulLoginListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_logs_successful_login(): void
    {
        $user = User::factory()->create();
        $event = new Login('web', $user, false);
        $listener = new LogSuccessfulLogin;

        // Clear any existing activity logs
        ActivityLog::truncate();

        $listener->handle($event);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLog::NAME_USER,
            'description' => ActivityLog::DESCRIPTION_USER_LOGIN,
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);
    }

    public function test_listener_has_handle_method(): void
    {
        $listener = new LogSuccessfulLogin;
        $this->assertTrue(method_exists($listener, 'handle'));
    }
}
