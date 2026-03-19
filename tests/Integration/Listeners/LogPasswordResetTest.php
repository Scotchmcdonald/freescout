<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Listeners\LogPasswordReset;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Tests\IntegrationTestCase;

class LogPasswordResetTest extends IntegrationTestCase
{
    public function test_handle_creates_activity_log_entry(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/reset-password', 'POST');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $event = new PasswordReset($user);
        $listener = new LogPasswordReset;

        $this->app->instance('request', $request);

        $listener->handle($event);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLog::NAME_USER,
            'description' => ActivityLog::DESCRIPTION_USER_PASSWORD_RESET,
            'causer_id' => $user->id,
            'causer_type' => get_class($user),
        ]);
    }

    public function test_handle_includes_ip_address_in_properties(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/reset-password', 'POST');
        $request->server->set('REMOTE_ADDR', '10.0.0.5');

        $event = new PasswordReset($user);
        $listener = new LogPasswordReset;

        $this->app->instance('request', $request);

        $listener->handle($event);

        $log = ActivityLog::where('description', ActivityLog::DESCRIPTION_USER_PASSWORD_RESET)
            ->where('causer_id', $user->id)
            ->latest()
            ->first();
        $properties = $log->properties;

        $this->assertEquals('10.0.0.5', $properties['ip']);
    }

    public function test_handle_is_caused_by_user(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $request = Request::create('/reset-password', 'POST');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $event = new PasswordReset($user);
        $listener = new LogPasswordReset;

        $this->app->instance('request', $request);

        $listener->handle($event);

        $log = ActivityLog::where('description', ActivityLog::DESCRIPTION_USER_PASSWORD_RESET)
            ->latest()
            ->first();

        $this->assertEquals($user->id, $log->causer_id);
        $this->assertEquals(get_class($user), $log->causer_type);
    }

    public function test_handle_does_not_throw_exception_on_error(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/reset-password', 'POST');

        $event = new PasswordReset($user);
        $listener = new LogPasswordReset;

        $this->app->instance('request', $request);

        $listener->handle($event);

        $this->assertDatabaseHas('activity_log', [
            'description' => ActivityLog::DESCRIPTION_USER_PASSWORD_RESET,
            'causer_id' => $user->id,
            'causer_type' => get_class($user),
        ]);
    }

    public function test_listener_can_be_instantiated(): void
    {
        $listener = new LogPasswordReset;

        $this->assertInstanceOf(LogPasswordReset::class, $listener);
    }
}
