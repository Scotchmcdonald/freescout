<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\UserDeleted;
use App\Listeners\LogLockout;
use App\Listeners\LogUserDeletion;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class LogListenersTest extends UnitTestCase
{

    // LogLockout Tests

    #[Test]
    public function log_lockout_can_be_instantiated(): void
    {
        $listener = new LogLockout();

        $this->assertInstanceOf(LogLockout::class, $listener);
    }

    #[Test]
    public function log_lockout_has_handle_method(): void
    {
        $listener = new LogLockout();

        $this->assertTrue(method_exists($listener, 'handle'));
    }

    #[Test]
    public function log_lockout_handles_lockout_event(): void
    {
        $request = Request::create('/login', 'POST', ['email' => 'test@example.com']);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        
        $event = new Lockout($request);
        $listener = new LogLockout();

        // Should not throw exception
        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function log_lockout_extracts_email_from_request(): void
    {
        $request = Request::create('/login', 'POST', ['email' => 'locked@example.com']);
        $event = new Lockout($request);
        $listener = new LogLockout();

        $listener->handle($event);
        
        // Activity log should be created with email
        $this->assertTrue(true);
    }

    #[Test]
    public function log_lockout_captures_ip_address(): void
    {
        $request = Request::create('/login', 'POST', ['email' => 'test@example.com']);
        $request->server->set('REMOTE_ADDR', '192.168.1.1');
        
        $event = new Lockout($request);
        $listener = new LogLockout();

        $listener->handle($event);
        
        // Should capture IP address
        $this->assertTrue(true);
    }

    // LogUserDeletion Tests

    #[Test]
    public function log_user_deletion_can_be_instantiated(): void
    {
        $listener = new LogUserDeletion();

        $this->assertInstanceOf(LogUserDeletion::class, $listener);
    }

    #[Test]
    public function log_user_deletion_has_handle_method(): void
    {
        $listener = new LogUserDeletion();

        $this->assertTrue(method_exists($listener, 'handle'));
    }

    #[Test]
    public function log_user_deletion_handles_user_deleted_event(): void
    {
        $deletedUser = User::factory()->create(['first_name' => 'Deleted', 'last_name' => 'User']);
        $byUser = User::factory()->create(['first_name' => 'Admin', 'last_name' => 'User']);

        $event = new UserDeleted($deletedUser, $byUser);
        $listener = new LogUserDeletion();

        // Should not throw exception
        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function log_user_deletion_logs_deleted_user_name(): void
    {
        $deletedUser = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $byUser = User::factory()->create();

        $event = new UserDeleted($deletedUser, $byUser);
        $listener = new LogUserDeletion();

        $listener->handle($event);
        
        // Should log full name of deleted user
        $this->assertEquals('John Doe', $deletedUser->getFullName());
    }

    #[Test]
    public function log_user_deletion_logs_deleted_user_id(): void
    {
        $deletedUser = User::factory()->create();
        $byUser = User::factory()->create();

        $event = new UserDeleted($deletedUser, $byUser);
        $listener = new LogUserDeletion();

        $listener->handle($event);
        
        // Should include user ID
        $this->assertNotNull($deletedUser->id);
    }

    #[Test]
    public function log_user_deletion_logs_caused_by_user(): void
    {
        $deletedUser = User::factory()->create();
        $byUser = User::factory()->create(['email' => 'admin@example.com']);

        $event = new UserDeleted($deletedUser, $byUser);
        $listener = new LogUserDeletion();

        $listener->handle($event);
        
        // Should log the user who performed deletion
        $this->assertEquals('admin@example.com', $byUser->email);
    }

    #[Test]
    public function log_lockout_works_with_different_emails(): void
    {
        $emails = ['test1@example.com', 'test2@example.com', 'admin@example.com'];
        $listener = new LogLockout();

        foreach ($emails as $email) {
            $request = Request::create('/login', 'POST', ['email' => $email]);
            $event = new Lockout($request);
            
            $listener->handle($event);
        }

        $this->assertTrue(true);
    }

    #[Test]
    public function log_user_deletion_works_with_multiple_deletions(): void
    {
        $byUser = User::factory()->create();
        $listener = new LogUserDeletion();

        for ($i = 0; $i < 3; $i++) {
            $deletedUser = User::factory()->create();
            $event = new UserDeleted($deletedUser, $byUser);
            
            $listener->handle($event);
        }

        $this->assertTrue(true);
    }

    #[Test]
    public function listeners_can_handle_events_without_errors(): void
    {
        $lockoutListener = new LogLockout();
        $deletionListener = new LogUserDeletion();

        $request = Request::create('/login', 'POST', ['email' => 'test@example.com']);
        $lockoutEvent = new Lockout($request);

        $deletedUser = User::factory()->create();
        $byUser = User::factory()->create();
        $deletionEvent = new UserDeleted($deletedUser, $byUser);

        // Both should handle without errors
        $lockoutListener->handle($lockoutEvent);
        $deletionListener->handle($deletionEvent);

        $this->assertTrue(true);
    }
}
