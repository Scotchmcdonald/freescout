<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Listeners\LogSuccessfulLogout;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogSuccessfulLogoutListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_logs_successful_logout(): void
    {
        $user = User::factory()->create();
        $event = new Logout('web', $user);
        $listener = new LogSuccessfulLogout;

        // Clear any existing activity logs
        ActivityLog::truncate();

        $listener->handle($event);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLog::NAME_USER,
            'description' => ActivityLog::DESCRIPTION_USER_LOGOUT,
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);
    }

    public function test_listener_has_handle_method(): void
    {
        $listener = new LogSuccessfulLogout;
        $this->assertTrue(method_exists($listener, 'handle'));
    }
}
