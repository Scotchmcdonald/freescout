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

    public function test_activity_log_with_null_properties(): void
    {
        $log = ActivityLog::factory()->create(['properties' => null]);

        $this->assertNull($log->properties);
    }

    public function test_activity_log_with_empty_properties_array(): void
    {
        $log = ActivityLog::factory()->create(['properties' => []]);

        $this->assertIsArray($log->properties);
        $this->assertEmpty($log->properties);
    }

    public function test_activity_log_with_null_batch_uuid(): void
    {
        $log = ActivityLog::factory()->create(['batch_uuid' => null]);

        $this->assertNull($log->batch_uuid);
    }

    public function test_activity_log_with_batch_uuid(): void
    {
        $uuid = \Illuminate\Support\Str::uuid()->toString();
        $log = ActivityLog::factory()->create(['batch_uuid' => $uuid]);

        $this->assertEquals($uuid, $log->batch_uuid);
    }

    public function test_multiple_activity_logs_for_same_subject(): void
    {
        $conversation = Conversation::factory()->create();
        
        ActivityLog::factory()->count(3)->create([
            'subject_type' => Conversation::class,
            'subject_id' => $conversation->id,
        ]);

        $logs = ActivityLog::forSubject($conversation)->get();

        $this->assertCount(3, $logs);
    }

    public function test_scope_filters_can_be_combined(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        
        ActivityLog::factory()->create([
            'log_name' => 'conversation',
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'subject_type' => Conversation::class,
            'subject_id' => $conversation->id,
        ]);
        
        ActivityLog::factory()->create([
            'log_name' => 'other',
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'subject_type' => Conversation::class,
            'subject_id' => $conversation->id,
        ]);

        $logs = ActivityLog::inLog('conversation')
            ->causedBy($user)
            ->forSubject($conversation)
            ->get();

        $this->assertCount(1, $logs);
        $this->assertEquals('conversation', $logs->first()->log_name);
    }

    public function test_created_at_and_updated_at_cast_to_datetime(): void
    {
        $log = ActivityLog::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $log->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $log->updated_at);
    }
}
