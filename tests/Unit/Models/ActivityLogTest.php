<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\ActivityLog;
use App\Models\User;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for ActivityLog Model
 * Following TESTING_GUIDE.md - using test_ prefix, UnitTestCase base class
 */
class ActivityLogTest extends UnitTestCase
{
    // ===== MODEL CREATION TESTS =====

    public function test_activity_log_can_be_created(): void
    {
        $log = ActivityLog::factory()->create([
            'log_name' => ActivityLog::NAME_USER,
            'description' => ActivityLog::DESCRIPTION_USER_LOGIN,
        ]);
        
        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertDatabaseHas('activity_log', [
            'id' => $log->id,
            'log_name' => ActivityLog::NAME_USER,
        ]);
    }

    public function test_activity_log_has_correct_fillable_attributes(): void
    {
        $log = new ActivityLog();
        
        $this->assertContains('log_name', $log->getFillable());
        $this->assertContains('description', $log->getFillable());
        $this->assertContains('subject_type', $log->getFillable());
        $this->assertContains('subject_id', $log->getFillable());
        $this->assertContains('causer_type', $log->getFillable());
        $this->assertContains('causer_id', $log->getFillable());
    }

    public function test_activity_log_uses_has_factory_trait(): void
    {
        $log = ActivityLog::factory()->create();
        
        $this->assertInstanceOf(ActivityLog::class, $log);
    }

    public function test_activity_log_uses_correct_table(): void
    {
        $log = new ActivityLog();
        
        $this->assertEquals('activity_log', $log->getTable());
    }

    // ===== LOG NAME CONSTANT TESTS =====

    public function test_name_user_constant_exists(): void
    {
        $this->assertEquals('users', ActivityLog::NAME_USER);
    }

    public function test_name_out_emails_constant_exists(): void
    {
        $this->assertEquals('out_emails', ActivityLog::NAME_OUT_EMAILS);
    }

    public function test_name_emails_sending_constant_exists(): void
    {
        $this->assertEquals('send_errors', ActivityLog::NAME_EMAILS_SENDING);
    }

    public function test_name_emails_fetching_constant_exists(): void
    {
        $this->assertEquals('fetch_errors', ActivityLog::NAME_EMAILS_FETCHING);
    }

    public function test_name_system_constant_exists(): void
    {
        $this->assertEquals('system', ActivityLog::NAME_SYSTEM);
    }

    public function test_name_app_logs_constant_exists(): void
    {
        $this->assertEquals('app', ActivityLog::NAME_APP_LOGS);
    }

    // ===== DESCRIPTION CONSTANT TESTS =====

    public function test_description_user_login_constant_exists(): void
    {
        $this->assertEquals('login', ActivityLog::DESCRIPTION_USER_LOGIN);
    }

    public function test_description_user_logout_constant_exists(): void
    {
        $this->assertEquals('logout', ActivityLog::DESCRIPTION_USER_LOGOUT);
    }

    public function test_description_user_register_constant_exists(): void
    {
        $this->assertEquals('register', ActivityLog::DESCRIPTION_USER_REGISTER);
    }

    public function test_description_user_locked_constant_exists(): void
    {
        $this->assertEquals('locked', ActivityLog::DESCRIPTION_USER_LOCKED);
    }

    public function test_description_user_login_failed_constant_exists(): void
    {
        $this->assertEquals('login_failed', ActivityLog::DESCRIPTION_USER_LOGIN_FAILED);
    }

    public function test_description_user_password_reset_constant_exists(): void
    {
        $this->assertEquals('password_reset', ActivityLog::DESCRIPTION_USER_PASSWORD_RESET);
    }

    public function test_description_user_deleted_constant_exists(): void
    {
        $this->assertEquals('user_deleted', ActivityLog::DESCRIPTION_USER_DELETED);
    }

    // ===== RELATIONSHIP TESTS =====

    public function test_activity_log_has_subject_morph_relationship(): void
    {
        $user = User::factory()->create();
        $log = ActivityLog::factory()->create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);
        
        $this->assertInstanceOf(User::class, $log->subject);
        $this->assertEquals($user->id, $log->subject->id);
    }

    public function test_activity_log_has_causer_morph_relationship(): void
    {
        $user = User::factory()->create();
        $log = ActivityLog::factory()->create([
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);
        
        $this->assertInstanceOf(User::class, $log->causer);
        $this->assertEquals($user->id, $log->causer->id);
    }

    public function test_activity_log_user_method_returns_user_causer(): void
    {
        $user = User::factory()->create();
        $log = ActivityLog::factory()->create([
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);
        
        $this->assertInstanceOf(User::class, $log->user());
        $this->assertEquals($user->id, $log->user()->id);
    }

    public function test_activity_log_user_method_returns_null_for_non_user_causer(): void
    {
        $log = ActivityLog::factory()->create([
            'causer_type' => null,
            'causer_id' => null,
        ]);
        
        $this->assertNull($log->user());
    }

    public function test_activity_log_subject_can_be_null(): void
    {
        $log = ActivityLog::factory()->create([
            'subject_type' => null,
            'subject_id' => null,
        ]);
        
        $this->assertNull($log->subject);
    }

    public function test_activity_log_causer_can_be_null(): void
    {
        $log = ActivityLog::factory()->create([
            'causer_type' => null,
            'causer_id' => null,
        ]);
        
        $this->assertNull($log->causer);
    }

    // ===== CAST TESTS =====

    public function test_properties_are_cast_to_json(): void
    {
        $properties = ['ip' => '127.0.0.1', 'user_agent' => 'Mozilla'];
        $log = ActivityLog::factory()->create(['properties' => $properties]);
        
        $this->assertEquals($properties, $log->properties);
        $this->assertIsArray($log->properties);
    }

    public function test_created_at_is_cast_to_datetime(): void
    {
        $log = ActivityLog::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $log->created_at);
    }

    public function test_updated_at_is_cast_to_datetime(): void
    {
        $log = ActivityLog::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $log->updated_at);
    }

    // ===== SCOPE TESTS =====

    public function test_in_log_scope_filters_by_log_name(): void
    {
        ActivityLog::factory()->create(['log_name' => ActivityLog::NAME_USER]);
        ActivityLog::factory()->create(['log_name' => ActivityLog::NAME_SYSTEM]);
        ActivityLog::factory()->create(['log_name' => ActivityLog::NAME_USER]);
        
        $userLogs = ActivityLog::inLog(ActivityLog::NAME_USER)->get();
        
        $this->assertCount(2, $userLogs);
    }

    public function test_caused_by_scope_filters_by_causer(): void
    {
        $user = User::factory()->create();
        ActivityLog::factory()->create([
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);
        ActivityLog::factory()->create([
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);
        ActivityLog::factory()->create([
            'causer_type' => null,
            'causer_id' => null,
        ]);
        
        $userLogs = ActivityLog::causedBy($user)->get();
        
        $this->assertCount(2, $userLogs);
    }

    public function test_for_subject_scope_filters_by_subject(): void
    {
        $user = User::factory()->create();
        ActivityLog::factory()->create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);
        ActivityLog::factory()->create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);
        ActivityLog::factory()->create([
            'subject_type' => null,
            'subject_id' => null,
        ]);
        
        $subjectLogs = ActivityLog::forSubject($user)->get();
        
        $this->assertCount(2, $subjectLogs);
    }

    // ===== QUERY TESTS =====

    public function test_can_query_by_description(): void
    {
        ActivityLog::factory()->create(['description' => ActivityLog::DESCRIPTION_USER_LOGIN]);
        ActivityLog::factory()->create(['description' => ActivityLog::DESCRIPTION_USER_LOGOUT]);
        
        $loginLogs = ActivityLog::where('description', ActivityLog::DESCRIPTION_USER_LOGIN)->get();
        
        $this->assertCount(1, $loginLogs);
    }

    public function test_can_query_by_batch_uuid(): void
    {
        $batchUuid = 'test-batch-uuid';
        ActivityLog::factory()->count(3)->create(['batch_uuid' => $batchUuid]);
        ActivityLog::factory()->create(['batch_uuid' => 'different-uuid']);
        
        $batchLogs = ActivityLog::where('batch_uuid', $batchUuid)->get();
        
        $this->assertCount(3, $batchLogs);
    }

    public function test_can_order_by_created_at(): void
    {
        $log1 = ActivityLog::factory()->create(['created_at' => now()->subHours(2)]);
        $log2 = ActivityLog::factory()->create(['created_at' => now()->subHour()]);
        $log3 = ActivityLog::factory()->create(['created_at' => now()]);
        
        $orderedLogs = ActivityLog::orderBy('created_at', 'desc')->get();
        
        $this->assertEquals($log3->id, $orderedLogs->first()->id);
        $this->assertEquals($log1->id, $orderedLogs->last()->id);
    }

    // ===== EDGE CASES =====

    public function test_activity_log_with_null_properties(): void
    {
        $log = ActivityLog::factory()->create(['properties' => null]);
        
        $this->assertNull($log->properties);
    }

    public function test_activity_log_with_empty_properties_array(): void
    {
        $log = ActivityLog::factory()->create(['properties' => []]);
        
        $this->assertEquals([], $log->properties);
    }

    public function test_activity_log_with_null_batch_uuid(): void
    {
        $log = ActivityLog::factory()->create(['batch_uuid' => null]);
        
        $this->assertNull($log->batch_uuid);
    }

    public function test_activity_log_can_be_updated(): void
    {
        $log = ActivityLog::factory()->create(['description' => 'old_description']);
        
        $log->update(['description' => 'new_description']);
        
        $this->assertEquals('new_description', $log->fresh()->description);
    }

    public function test_activity_log_can_be_deleted(): void
    {
        $log = ActivityLog::factory()->create();
        $id = $log->id;
        
        $log->delete();
        
        $this->assertDatabaseMissing('activity_log', ['id' => $id]);
    }

    public function test_multiple_logs_can_exist_for_same_user(): void
    {
        $user = User::factory()->create();
        
        ActivityLog::factory()->count(5)->create([
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);
        
        $this->assertCount(5, ActivityLog::causedBy($user)->get());
    }

    public function test_activity_log_with_complex_properties(): void
    {
        $properties = [
            'ip' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'attributes' => ['key1' => 'value1', 'key2' => 'value2'],
            'old' => ['field' => 'old_value'],
            'new' => ['field' => 'new_value'],
        ];
        
        $log = ActivityLog::factory()->create(['properties' => $properties]);
        
        $this->assertEquals($properties, $log->properties);
        $this->assertEquals('192.168.1.1', $log->properties['ip']);
    }

    public function test_can_create_login_activity_log(): void
    {
        $user = User::factory()->create();
        
        $log = ActivityLog::create([
            'log_name' => ActivityLog::NAME_USER,
            'description' => ActivityLog::DESCRIPTION_USER_LOGIN,
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'properties' => ['ip' => '127.0.0.1'],
        ]);
        
        $this->assertEquals(ActivityLog::NAME_USER, $log->log_name);
        $this->assertEquals(ActivityLog::DESCRIPTION_USER_LOGIN, $log->description);
    }

    public function test_can_create_logout_activity_log(): void
    {
        $user = User::factory()->create();
        
        $log = ActivityLog::create([
            'log_name' => ActivityLog::NAME_USER,
            'description' => ActivityLog::DESCRIPTION_USER_LOGOUT,
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);
        
        $this->assertEquals(ActivityLog::DESCRIPTION_USER_LOGOUT, $log->description);
    }

    public function test_can_create_failed_login_activity_log(): void
    {
        $log = ActivityLog::create([
            'log_name' => ActivityLog::NAME_USER,
            'description' => ActivityLog::DESCRIPTION_USER_LOGIN_FAILED,
            'properties' => ['email' => 'test@example.com', 'ip' => '127.0.0.1'],
        ]);
        
        $this->assertEquals(ActivityLog::DESCRIPTION_USER_LOGIN_FAILED, $log->description);
        $this->assertEquals('test@example.com', $log->properties['email']);
    }

    public function test_activity_log_timestamps_are_automatically_set(): void
    {
        $log = ActivityLog::factory()->create();
        
        $this->assertNotNull($log->created_at);
        $this->assertNotNull($log->updated_at);
    }

    public function test_activity_log_description_can_be_custom_string(): void
    {
        $log = ActivityLog::factory()->create(['description' => 'custom_action']);
        
        $this->assertEquals('custom_action', $log->description);
    }

    public function test_can_combine_multiple_scopes(): void
    {
        $user = User::factory()->create();
        
        ActivityLog::factory()->create([
            'log_name' => ActivityLog::NAME_USER,
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);
        
        ActivityLog::factory()->create([
            'log_name' => ActivityLog::NAME_SYSTEM,
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);
        
        $logs = ActivityLog::inLog(ActivityLog::NAME_USER)
            ->causedBy($user)
            ->get();
        
        $this->assertCount(1, $logs);
    }

    public function test_activity_log_can_store_large_properties(): void
    {
        $largeProperties = [
            'data' => str_repeat('x', 1000),
            'additional_info' => array_fill(0, 50, 'item'),
        ];
        
        $log = ActivityLog::factory()->create(['properties' => $largeProperties]);
        
        $this->assertEquals($largeProperties, $log->fresh()->properties);
    }
}
