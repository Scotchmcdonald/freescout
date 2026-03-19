<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Listeners\LogRegisteredUser;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Tests\IntegrationTestCase;

class LogRegisteredUserTest extends IntegrationTestCase
{
    public function test_handle_creates_activity_log_entry(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/register', 'POST');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $event = new Registered($user);
        $listener = new LogRegisteredUser;

        $this->app->instance('request', $request);

        $listener->handle($event);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLog::NAME_USER,
            'description' => ActivityLog::DESCRIPTION_USER_REGISTER,
            'causer_id' => $user->id,
            'causer_type' => get_class($user),
        ]);
    }

    public function test_handle_includes_ip_address_in_properties(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/register', 'POST');
        $request->server->set('REMOTE_ADDR', '172.16.0.10');

        $event = new Registered($user);
        $listener = new LogRegisteredUser;

        $this->app->instance('request', $request);

        $listener->handle($event);

        $log = ActivityLog::where('description', ActivityLog::DESCRIPTION_USER_REGISTER)
            ->where('causer_id', $user->id)
            ->latest()
            ->first();
        $properties = $log->properties;

        $this->assertEquals('172.16.0.10', $properties['ip']);
    }

    public function test_handle_is_caused_by_registered_user(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
        ]);
        $request = Request::create('/register', 'POST');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $event = new Registered($user);
        $listener = new LogRegisteredUser;

        $this->app->instance('request', $request);

        $listener->handle($event);

        $log = ActivityLog::where('description', ActivityLog::DESCRIPTION_USER_REGISTER)
            ->latest()
            ->first();

        $this->assertEquals($user->id, $log->causer_id);
        $this->assertEquals(get_class($user), $log->causer_type);
    }

    public function test_handle_does_not_throw_exception_on_error(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/register', 'POST');

        $event = new Registered($user);
        $listener = new LogRegisteredUser;

        $this->app->instance('request', $request);

        $listener->handle($event);

        $this->assertDatabaseHas('activity_log', [
            'description' => ActivityLog::DESCRIPTION_USER_REGISTER,
            'causer_id' => $user->id,
            'causer_type' => get_class($user),
        ]);
    }

    public function test_listener_can_be_instantiated(): void
    {
        $listener = new LogRegisteredUser;

        $this->assertInstanceOf(LogRegisteredUser::class, $listener);
    }
}
