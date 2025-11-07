<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_can_be_instantiated(): void
    {
        $log = new ActivityLog();
        $this->assertInstanceOf(ActivityLog::class, $log);
    }

    public function test_model_has_fillable_attributes(): void
    {
        $log = new ActivityLog([
            'causer_id' => 1,
            'description' => 'test activity',
            'subject_id' => 123,
            'subject_type' => 'App\Models\Conversation',
        ]);

        $this->assertEquals(1, $log->causer_id);
        $this->assertEquals('test activity', $log->description);
        $this->assertEquals(123, $log->subject_id);
        $this->assertEquals('App\Models\Conversation', $log->subject_type);
    }

    public function test_properties_cast_to_json(): void
    {
        $properties = ['old' => 'value1', 'new' => 'value2'];
        $log = ActivityLog::factory()->create([
            'properties' => $properties,
        ]);

        $this->assertIsArray($log->properties);
        $this->assertEquals($properties, $log->properties);
        $this->assertDatabaseHas('activity_log', [
            'id' => $log->id,
        ]);
    }

    public function test_scope_in_log_filters_by_log_name(): void
    {
        ActivityLog::factory()->create(['log_name' => 'default']);
        ActivityLog::factory()->create(['log_name' => 'custom']);
        ActivityLog::factory()->create(['log_name' => 'default']);

        $filtered = ActivityLog::inLog('default')->get();

        $this->assertCount(2, $filtered);
        $this->assertTrue($filtered->every(fn($log) => $log->log_name === 'default'));
    }

    public function test_scope_caused_by_filters_by_causer(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $conversation = Conversation::factory()->create();

        ActivityLog::factory()->create([
            'causer_type' => User::class,
            'causer_id' => $user1->id,
            'subject_type' => Conversation::class,
            'subject_id' => $conversation->id,
        ]);
        
        ActivityLog::factory()->create([
            'causer_type' => User::class,
            'causer_id' => $user2->id,
            'subject_type' => Conversation::class,
            'subject_id' => $conversation->id,
        ]);

        $filtered = ActivityLog::causedBy($user1)->get();

        $this->assertCount(1, $filtered);
        $this->assertEquals($user1->id, $filtered->first()->causer_id);
    }

    public function test_scope_for_subject_filters_by_subject(): void
    {
        $conversation1 = Conversation::factory()->create();
        $conversation2 = Conversation::factory()->create();

        ActivityLog::factory()->create([
            'subject_type' => Conversation::class,
            'subject_id' => $conversation1->id,
        ]);
        
        ActivityLog::factory()->create([
            'subject_type' => Conversation::class,
            'subject_id' => $conversation2->id,
        ]);

        $filtered = ActivityLog::forSubject($conversation1)->get();

        $this->assertCount(1, $filtered);
        $this->assertEquals($conversation1->id, $filtered->first()->subject_id);
    }

    public function test_user_method_returns_user_when_causer_is_user(): void
    {
        $user = User::factory()->create();
        $log = ActivityLog::factory()->create([
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);

        $retrievedUser = $log->user();

        $this->assertInstanceOf(User::class, $retrievedUser);
        $this->assertEquals($user->id, $retrievedUser->id);
    }

    public function test_user_method_returns_null_when_causer_is_not_user(): void
    {
        $conversation = Conversation::factory()->create();
        $log = ActivityLog::factory()->create([
            'causer_type' => Conversation::class,
            'causer_id' => $conversation->id,
        ]);

        $user = $log->user();

        $this->assertNull($user);
    }

    public function test_subject_morph_relationship(): void
    {
        $conversation = Conversation::factory()->create();
        $log = ActivityLog::factory()->create([
            'subject_type' => Conversation::class,
            'subject_id' => $conversation->id,
        ]);

        $this->assertInstanceOf(Conversation::class, $log->subject);
        $this->assertEquals($conversation->id, $log->subject->id);
    }

    public function test_causer_morph_relationship(): void
    {
        $user = User::factory()->create();
        $log = ActivityLog::factory()->create([
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $log->causer);
        $this->assertEquals($user->id, $log->causer->id);
    }
}
