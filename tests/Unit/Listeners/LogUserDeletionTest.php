<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\UserDeleted;
use App\Listeners\LogUserDeletion;
use App\Models\ActivityLog;
use App\Models\User;
use Tests\UnitTestCase;

class LogUserDeletionTest extends UnitTestCase
{
    public function test_handle_creates_activity_log_entry(): void
    {
        $byUser = User::factory()->create(['first_name' => 'Admin']);
        $deletedUser = User::factory()->create(['first_name' => 'DeletedUser']);
        
        $event = new UserDeleted($deletedUser, $byUser);
        $listener = new LogUserDeletion();
        
        $listener->handle($event);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => ActivityLog::NAME_USER,
            'description' => ActivityLog::DESCRIPTION_USER_DELETED,
            'causer_id' => $byUser->id,
            'causer_type' => get_class($byUser),
        ]);
    }

    public function test_handle_includes_deleted_user_details_in_properties(): void
    {
        $byUser = User::factory()->create(['first_name' => 'Admin']);
        $deletedUser = User::factory()->create([
            'id' => 123,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        
        $event = new UserDeleted($deletedUser, $byUser);
        $listener = new LogUserDeletion();
        
        $listener->handle($event);

        $log = ActivityLog::where('description', ActivityLog::DESCRIPTION_USER_DELETED)
            ->where('causer_id', $byUser->id)
            ->latest()
            ->first();
        $properties = $log->properties;
        
        $this->assertArrayHasKey('deleted_user', $properties);
        $this->assertStringContainsString('John Doe', $properties['deleted_user']);
        $this->assertStringContainsString('[123]', $properties['deleted_user']);
    }

    public function test_handle_is_caused_by_deleting_user(): void
    {
        $byUser = User::factory()->create([
            'id' => 5,
            'first_name' => 'Admin',
        ]);
        $deletedUser = User::factory()->create(['first_name' => 'User']);
        
        $event = new UserDeleted($deletedUser, $byUser);
        $listener = new LogUserDeletion();
        
        $listener->handle($event);

        $log = ActivityLog::where('description', ActivityLog::DESCRIPTION_USER_DELETED)
            ->latest()
            ->first();
        
        $this->assertEquals(5, $log->causer_id);
        $this->assertEquals(get_class($byUser), $log->causer_type);
    }

    public function test_handle_does_not_throw_exception_on_error(): void
    {
        $byUser = User::factory()->create();
        $deletedUser = User::factory()->create();
        
        $event = new UserDeleted($deletedUser, $byUser);
        $listener = new LogUserDeletion();

        // Should not throw exception
        try {
            $listener->handle($event);
            $this->expectNotToPerformAssertions();
        } catch (\Exception $e) {
            $this->fail('Listener should not throw exception: ' . $e->getMessage());
        }
    }

    public function test_listener_can_be_instantiated(): void
    {
        $listener = new LogUserDeletion();
        
        $this->assertInstanceOf(LogUserDeletion::class, $listener);
    }

    public function test_handle_includes_user_id_in_deleted_user_property(): void
    {
        $byUser = User::factory()->create();
        $deletedUser = User::factory()->create([
            'id' => 999,
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);
        
        $event = new UserDeleted($deletedUser, $byUser);
        $listener = new LogUserDeletion();
        
        $listener->handle($event);

        $log = ActivityLog::where('description', ActivityLog::DESCRIPTION_USER_DELETED)
            ->latest()
            ->first();
        
        $this->assertStringContainsString('999', $log->properties['deleted_user']);
    }
}
