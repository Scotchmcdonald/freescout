<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Conversation;
use App\Models\User;
use Tests\UnitTestCase;

/**
 * Tests for ActivityLog model methods added during Phase 1 implementation.
 */
class ActivityLogMethodsTest extends UnitTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    // ===== getEventDescription tests =====

    public function test_get_event_description_returns_string(): void
    {
        $log = ActivityLog::factory()->create([
            'description' => ActivityLog::DESCRIPTION_USER_CREATED,
        ]);

        $description = $log->getEventDescription();

        $this->assertIsString($description);
        $this->assertNotEmpty($description);
    }

    public function test_get_event_description_handles_user_created(): void
    {
        $log = ActivityLog::factory()->create([
            'description' => ActivityLog::DESCRIPTION_USER_CREATED,
        ]);

        $description = $log->getEventDescription();

        $this->assertStringContainsString('User', $description);
    }

    public function test_get_event_description_handles_conversation_status_changed(): void
    {
        $log = ActivityLog::factory()->create([
            'description' => ActivityLog::DESCRIPTION_CONVERSATION_STATUS_CHANGED,
        ]);

        $description = $log->getEventDescription();

        $this->assertStringContainsString('Status', $description);
    }

    public function test_get_event_description_handles_unknown_description(): void
    {
        $log = ActivityLog::factory()->create([
            'description' => 'unknown_event_type',
        ]);

        $description = $log->getEventDescription();

        $this->assertIsString($description);
    }

    public function test_get_event_description_handles_null_description(): void
    {
        $log = ActivityLog::factory()->create([
            'description' => null,
        ]);

        $description = $log->getEventDescription();

        $this->assertIsString($description);
    }

    // ===== getLogTitle tests =====

    public function test_get_log_title_returns_string(): void
    {
        $log = ActivityLog::factory()->create();

        $title = $log->getLogTitle();

        $this->assertIsString($title);
    }

    public function test_get_log_title_formats_subject_type(): void
    {
        $conversation = Conversation::factory()->create();
        $log = ActivityLog::factory()->create([
            'subject_type' => Conversation::class,
            'subject_id' => $conversation->id,
        ]);

        $title = $log->getLogTitle();

        $this->assertIsString($title);
    }

    // ===== formatColTitle tests =====

    public function test_format_col_title_formats_snake_case(): void
    {
        $result = ActivityLog::formatColTitle('user_created');

        $this->assertEquals('User Created', $result);
    }

    public function test_format_col_title_handles_single_word(): void
    {
        $result = ActivityLog::formatColTitle('created');

        $this->assertEquals('Created', $result);
    }

    public function test_format_col_title_handles_multiple_underscores(): void
    {
        $result = ActivityLog::formatColTitle('conversation_status_changed_by_user');

        $this->assertEquals('Conversation Status Changed By User', $result);
    }

    public function test_format_col_title_handles_empty_string(): void
    {
        $result = ActivityLog::formatColTitle('');

        $this->assertIsString($result);
    }

    // ===== getLogNames tests =====

    public function test_get_log_names_returns_array(): void
    {
        $logNames = ActivityLog::getLogNames();

        $this->assertIsArray($logNames);
    }

    public function test_get_log_names_contains_expected_keys(): void
    {
        // Create some logs with different names
        ActivityLog::factory()->create(['log_name' => 'default']);
        ActivityLog::factory()->create(['log_name' => 'conversation']);
        ActivityLog::factory()->create(['log_name' => 'user']);

        $logNames = ActivityLog::getLogNames();

        $this->assertContains('default', $logNames);
        $this->assertContains('conversation', $logNames);
        $this->assertContains('user', $logNames);
    }

    public function test_get_log_names_returns_unique_values(): void
    {
        ActivityLog::factory()->count(3)->create(['log_name' => 'default']);

        $logNames = ActivityLog::getLogNames();
        $uniqueNames = array_unique($logNames);

        $this->assertEquals(count($uniqueNames), count($logNames));
    }

    // ===== getAvailableLogs tests =====

    public function test_get_available_logs_returns_array(): void
    {
        $logs = ActivityLog::getAvailableLogs();

        $this->assertIsArray($logs);
    }

    public function test_get_available_logs_contains_formatted_names(): void
    {
        ActivityLog::factory()->create(['log_name' => 'user_activity']);

        $logs = ActivityLog::getAvailableLogs();

        // Should have formatted versions
        $this->assertIsArray($logs);
    }

    // ===== Description constants tests =====

    public function test_description_constants_exist(): void
    {
        $this->assertNotNull(ActivityLog::DESCRIPTION_USER_CREATED);
        $this->assertNotNull(ActivityLog::DESCRIPTION_CONVERSATION_STATUS_CHANGED);
        $this->assertNotNull(ActivityLog::DESCRIPTION_CONVERSATION_USER_CHANGED);
        $this->assertNotNull(ActivityLog::DESCRIPTION_CONVERSATION_DELETED);
    }

    public function test_description_constants_are_strings(): void
    {
        $this->assertIsString(ActivityLog::DESCRIPTION_USER_CREATED);
        $this->assertIsString(ActivityLog::DESCRIPTION_CONVERSATION_STATUS_CHANGED);
        $this->assertIsString(ActivityLog::DESCRIPTION_CONVERSATION_USER_CHANGED);
    }

    // ===== Email error description constants tests =====

    public function test_email_error_constants_exist(): void
    {
        $this->assertNotNull(ActivityLog::DESCRIPTION_EMAIL_SEND_ERROR_TO_CUSTOMER);
        $this->assertNotNull(ActivityLog::DESCRIPTION_EMAIL_SEND_ERROR_TO_USER);
        $this->assertNotNull(ActivityLog::DESCRIPTION_EMAIL_SEND_ERROR);
    }

    public function test_email_error_constants_are_strings(): void
    {
        $this->assertIsString(ActivityLog::DESCRIPTION_EMAIL_SEND_ERROR_TO_CUSTOMER);
        $this->assertIsString(ActivityLog::DESCRIPTION_EMAIL_SEND_ERROR_TO_USER);
        $this->assertIsString(ActivityLog::DESCRIPTION_EMAIL_SEND_ERROR);
    }

    // ===== Activity logging integration tests =====

    public function test_activity_log_created_with_conversation_subject(): void
    {
        $conversation = Conversation::factory()->create();

        $log = ActivityLog::factory()->create([
            'subject_type' => Conversation::class,
            'subject_id' => $conversation->id,
            'causer_type' => User::class,
            'causer_id' => $this->user->id,
            'description' => ActivityLog::DESCRIPTION_CONVERSATION_STATUS_CHANGED,
        ]);

        $this->assertInstanceOf(Conversation::class, $log->subject);
        $this->assertEquals($conversation->id, $log->subject->id);
    }

    public function test_activity_log_causer_relationship(): void
    {
        $log = ActivityLog::factory()->create([
            'causer_type' => User::class,
            'causer_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $log->causer);
        $this->assertEquals($this->user->id, $log->causer->id);
    }

    public function test_activity_log_with_properties(): void
    {
        $properties = [
            'old_status' => Conversation::STATUS_ACTIVE,
            'new_status' => Conversation::STATUS_CLOSED,
        ];

        $log = ActivityLog::factory()->create([
            'properties' => $properties,
            'description' => ActivityLog::DESCRIPTION_CONVERSATION_STATUS_CHANGED,
        ]);

        $this->assertEquals($properties, $log->properties);
    }

    // ===== Scopes tests =====

    public function test_scope_in_log_with_description_constants(): void
    {
        ActivityLog::factory()->create([
            'log_name' => 'conversation',
            'description' => ActivityLog::DESCRIPTION_CONVERSATION_STATUS_CHANGED,
        ]);
        ActivityLog::factory()->create([
            'log_name' => 'user',
            'description' => ActivityLog::DESCRIPTION_USER_CREATED,
        ]);

        $conversationLogs = ActivityLog::inLog('conversation')->get();
        $userLogs = ActivityLog::inLog('user')->get();

        $this->assertCount(1, $conversationLogs);
        $this->assertCount(1, $userLogs);
    }

    // ===== Available logs static property tests =====

    public function test_available_logs_static_array_exists(): void
    {
        $this->assertIsArray(ActivityLog::$available_logs);
    }

    public function test_available_logs_contains_expected_types(): void
    {
        $this->assertArrayHasKey('default', ActivityLog::$available_logs);
    }
}
