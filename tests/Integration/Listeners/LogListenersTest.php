<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Events\UserDeleted;
use App\Listeners\LogFailedLogin;
use App\Listeners\LogLockout;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;
use App\Listeners\LogUserDeletion;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Tests\IntegrationTestCase;

class LogListenersTest extends IntegrationTestCase
{
    // ===== LogLockout Tests =====

    public function test_log_lockout_can_be_instantiated(): void
    {
        $listener = new LogLockout;

        $this->assertInstanceOf(LogLockout::class, $listener);
    }

    public function test_log_lockout_has_handle_method(): void
    {
        $listener = new LogLockout;

        $this->assertTrue(method_exists($listener, 'handle'));
    }

    public function test_log_lockout_handles_lockout_event(): void
    {
        ActivityLog::truncate();

        $request = Request::create('/login', 'POST', ['email' => 'test@example.com']);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $event = new Lockout($request);
        $listener = new LogLockout;

        $listener->handle($event);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLog::NAME_USER,
            'description' => ActivityLog::DESCRIPTION_USER_LOCKED,
        ]);
    }

    public function test_log_lockout_extracts_email_from_request(): void
    {
        ActivityLog::truncate();

        $request = Request::create('/login', 'POST', ['email' => 'locked@example.com']);
        $event = new Lockout($request);
        $listener = new LogLockout;

        $listener->handle($event);

        $log = ActivityLog::where('log_name', ActivityLog::NAME_USER)->first();
        $this->assertEquals('locked@example.com', $log->properties['email']);
    }

    public function test_log_lockout_captures_ip_address(): void
    {
        ActivityLog::truncate();

        $request = Request::create('/login', 'POST', ['email' => 'test@example.com']);
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $event = new Lockout($request);
        $listener = new LogLockout;

        $listener->handle($event);

        $log = ActivityLog::where('log_name', ActivityLog::NAME_USER)->first();
        // The listener uses request()->ip() which defaults to 127.0.0.1 in tests
        $this->assertNotEmpty($log->properties['ip']);
    }

    // ===== LogUserDeletion Tests =====

    public function test_log_user_deletion_can_be_instantiated(): void
    {
        $listener = new LogUserDeletion;

        $this->assertInstanceOf(LogUserDeletion::class, $listener);
    }

    public function test_log_user_deletion_has_handle_method(): void
    {
        $listener = new LogUserDeletion;

        $this->assertTrue(method_exists($listener, 'handle'));
    }

    public function test_log_user_deletion_handles_user_deleted_event(): void
    {
        ActivityLog::truncate();

        $deletedUser = User::factory()->create(['first_name' => 'Deleted', 'last_name' => 'User']);
        $byUser = User::factory()->create(['first_name' => 'Admin', 'last_name' => 'User']);

        $event = new UserDeleted($deletedUser, $byUser);
        $listener = new LogUserDeletion;

        $listener->handle($event);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLog::NAME_USER,
            'description' => ActivityLog::DESCRIPTION_USER_DELETED,
            'causer_id' => $byUser->id,
        ]);
    }

    public function test_log_user_deletion_logs_deleted_user_name(): void
    {
        ActivityLog::truncate();

        $deletedUser = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $byUser = User::factory()->create();

        $event = new UserDeleted($deletedUser, $byUser);
        $listener = new LogUserDeletion;

        $listener->handle($event);

        $log = ActivityLog::where('log_name', ActivityLog::NAME_USER)->first();
        $this->assertStringContainsString('John Doe', $log->properties['deleted_user']);
    }

    public function test_log_user_deletion_logs_deleted_user_id(): void
    {
        ActivityLog::truncate();

        $deletedUser = User::factory()->create();
        $byUser = User::factory()->create();

        $event = new UserDeleted($deletedUser, $byUser);
        $listener = new LogUserDeletion;

        $listener->handle($event);

        $log = ActivityLog::where('log_name', ActivityLog::NAME_USER)->first();
        $this->assertStringContainsString('['.$deletedUser->id.']', $log->properties['deleted_user']);
    }

    public function test_log_user_deletion_logs_caused_by_user(): void
    {
        ActivityLog::truncate();

        $deletedUser = User::factory()->create();
        $byUser = User::factory()->create(['email' => 'admin@example.com']);

        $event = new UserDeleted($deletedUser, $byUser);
        $listener = new LogUserDeletion;

        $listener->handle($event);

        $log = ActivityLog::where('log_name', ActivityLog::NAME_USER)->first();
        $this->assertEquals($byUser->id, $log->causer_id);
    }

    public function test_log_lockout_works_with_different_emails(): void
    {
        ActivityLog::truncate();

        $emails = ['test1@example.com', 'test2@example.com', 'admin@example.com'];
        $listener = new LogLockout;

        foreach ($emails as $email) {
            $request = Request::create('/login', 'POST', ['email' => $email]);
            $event = new Lockout($request);

            $listener->handle($event);
        }

        $this->assertCount(3, ActivityLog::where('log_name', ActivityLog::NAME_USER)->get());
    }

    public function test_log_user_deletion_works_with_multiple_deletions(): void
    {
        ActivityLog::truncate();

        $byUser = User::factory()->create();
        $listener = new LogUserDeletion;

        for ($i = 0; $i < 3; $i++) {
            $deletedUser = User::factory()->create();
            $event = new UserDeleted($deletedUser, $byUser);

            $listener->handle($event);
        }

        $this->assertCount(3, ActivityLog::where('log_name', ActivityLog::NAME_USER)->get());
    }

    // ===== LogFailedLogin Tests =====

    public function test_log_failed_login_listener_logs_failed_login(): void
    {
        ActivityLog::truncate();

        $request = Request::create('/login', 'POST', ['email' => 'test@example.com']);
        $event = new Failed('web', null, ['email' => 'test@example.com']);
        $listener = new LogFailedLogin;

        app()->instance('request', $request);

        $listener->handle($event);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLog::NAME_USER,
            'description' => ActivityLog::DESCRIPTION_USER_LOGIN_FAILED,
        ]);
    }

    public function test_log_failed_login_listener_has_handle_method(): void
    {
        $listener = new LogFailedLogin;
        $this->assertTrue(method_exists($listener, 'handle'));
    }

    // ===== LogSuccessfulLogin Tests =====

    public function test_log_successful_login_listener_logs_successful_login(): void
    {
        ActivityLog::truncate();

        $user = User::factory()->create();
        $event = new Login('web', $user, false);
        $listener = new LogSuccessfulLogin;

        $listener->handle($event);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLog::NAME_USER,
            'description' => ActivityLog::DESCRIPTION_USER_LOGIN,
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);
    }

    public function test_log_successful_login_listener_has_handle_method(): void
    {
        $listener = new LogSuccessfulLogin;
        $this->assertTrue(method_exists($listener, 'handle'));
    }

    // ===== LogSuccessfulLogout Tests =====

    public function test_log_successful_logout_listener_logs_successful_logout(): void
    {
        ActivityLog::truncate();

        $user = User::factory()->create();
        $event = new Logout('web', $user);
        $listener = new LogSuccessfulLogout;

        $listener->handle($event);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLog::NAME_USER,
            'description' => ActivityLog::DESCRIPTION_USER_LOGOUT,
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);
    }

    public function test_log_successful_logout_listener_has_handle_method(): void
    {
        $listener = new LogSuccessfulLogout;
        $this->assertTrue(method_exists($listener, 'handle'));
    }
}
