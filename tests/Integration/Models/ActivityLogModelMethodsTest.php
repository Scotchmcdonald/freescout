<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\ActivityLog;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\User;
use Tests\IntegrationTestCase;

/**
 * Comprehensive tests for ActivityLog model methods added during Phase 1 migration.
 */
class ActivityLogModelMethodsTest extends IntegrationTestCase
{
    protected User $user;
    protected Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->mailbox = Mailbox::factory()->create();
    }

    // ===== getEventDescription tests =====

    public function test_get_event_description_returns_string(): void
    {
        $log = ActivityLog::factory()->create([
            'description' => 1,
        ]);

        $description = $log->getEventDescription();

        $this->assertIsString($description);
    }

    public function test_get_event_description_for_customer_error(): void
    {
        $log = ActivityLog::factory()->create([
            'description' => ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_TO_CUSTOMER,
        ]);

        $description = $log->getEventDescription();

        $this->assertNotEmpty($description);
        $this->assertStringContainsStringIgnoringCase('customer', strtolower($description));
    }

    public function test_get_event_description_for_invite_error(): void
    {
        $log = ActivityLog::factory()->create([
            'description' => ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_INVITE,
        ]);

        $description = $log->getEventDescription();

        $this->assertNotEmpty($description);
        $this->assertStringContainsStringIgnoringCase('invitation', strtolower($description));
    }

    public function test_get_event_description_for_password_changed_error(): void
    {
        $log = ActivityLog::factory()->create([
            'description' => ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_PASSWORD_CHANGED,
        ]);

        $description = $log->getEventDescription();

        $this->assertNotEmpty($description);
        $this->assertStringContainsStringIgnoringCase('password', strtolower($description));
    }

    public function test_get_event_description_for_alert_error(): void
    {
        $log = ActivityLog::factory()->create([
            'description' => ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_ALERT,
        ]);

        $description = $log->getEventDescription();

        $this->assertNotEmpty($description);
        $this->assertStringContainsStringIgnoringCase('alert', strtolower($description));
    }

    public function test_get_event_description_for_auto_reply_error(): void
    {
        $log = ActivityLog::factory()->create([
            'description' => ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_AUTO_REPLY,
        ]);

        $description = $log->getEventDescription();

        $this->assertNotEmpty($description);
        // Should contain 'auto reply' or 'auto-reply' or 'autoreply'
        $this->assertTrue(
            str_contains(strtolower($description), 'auto') ||
            str_contains(strtolower($description), 'reply')
        );
    }

    public function test_get_event_description_for_user_notification_error(): void
    {
        $log = ActivityLog::factory()->create([
            'description' => ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_USER_NOTIFICATION,
        ]);

        $description = $log->getEventDescription();

        $this->assertNotEmpty($description);
        $this->assertTrue(
            str_contains(strtolower($description), 'notification') ||
            str_contains(strtolower($description), 'user')
        );
    }

    public function test_get_event_description_for_system_error(): void
    {
        $log = ActivityLog::factory()->create([
            'description' => ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_SYSTEM,
        ]);

        $description = $log->getEventDescription();

        $this->assertNotEmpty($description);
        $this->assertStringContainsStringIgnoringCase('system', strtolower($description));
    }

    public function test_get_event_description_for_forward_error(): void
    {
        $log = ActivityLog::factory()->create([
            'description' => ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_FORWARD,
        ]);

        $description = $log->getEventDescription();

        $this->assertNotEmpty($description);
        $this->assertStringContainsStringIgnoringCase('forward', strtolower($description));
    }

    public function test_get_event_description_for_unknown_returns_fallback(): void
    {
        $log = ActivityLog::factory()->create([
            'description' => 9999, // Unknown description code
        ]);

        $description = $log->getEventDescription();

        // Should return empty string or generic description
        $this->assertIsString($description);
    }

    // ===== getLogTitle tests =====

    public function test_get_log_title_returns_string(): void
    {
        $log = ActivityLog::factory()->create([
            'log_name' => 'emails_sending',
        ]);

        $title = ActivityLog::getLogTitle('emails_sending');

        $this->assertIsString($title);
    }

    public function test_get_log_title_for_emails_sending(): void
    {
        $title = ActivityLog::getLogTitle('emails_sending');

        $this->assertNotEmpty($title);
    }

    public function test_get_log_title_for_unknown_log_name(): void
    {
        $title = ActivityLog::getLogTitle('unknown_log');

        // Should return the log name or a fallback
        $this->assertIsString($title);
    }

    // ===== formatColTitle tests =====

    public function test_format_col_title_formats_snake_case(): void
    {
        $formatted = ActivityLog::formatColTitle('created_at');

        $this->assertStringContainsString('Created', $formatted);
    }

    public function test_format_col_title_capitalizes_words(): void
    {
        $formatted = ActivityLog::formatColTitle('user_name');

        $this->assertEquals('User Name', $formatted);
    }

    public function test_format_col_title_handles_single_word(): void
    {
        $formatted = ActivityLog::formatColTitle('status');

        $this->assertEquals('Status', $formatted);
    }

    public function test_format_col_title_handles_empty_string(): void
    {
        $formatted = ActivityLog::formatColTitle('');

        $this->assertEquals('', $formatted);
    }

    // ===== getLogNames tests =====

    public function test_get_log_names_returns_array(): void
    {
        $names = ActivityLog::getLogNames();

        $this->assertIsArray($names);
    }

    public function test_get_log_names_includes_common_logs(): void
    {
        // Create logs to ensure they exist in DB
        ActivityLog::factory()->create(['log_name' => ActivityLog::NAME_EMAILS_SENDING]);
        ActivityLog::factory()->create(['log_name' => ActivityLog::NAME_USER]);
        ActivityLog::factory()->create(['log_name' => ActivityLog::NAME_SYSTEM]);

        $names = ActivityLog::getLogNames();

        // Check for common log types
        $this->assertContains(ActivityLog::NAME_EMAILS_SENDING, $names);
        $this->assertContains(ActivityLog::NAME_USER, $names);
        $this->assertContains(ActivityLog::NAME_SYSTEM, $names);
    }

    // ===== getAvailableLogs tests =====

    public function test_get_available_logs_returns_array(): void
    {
        $logs = ActivityLog::getAvailableLogs();

        $this->assertIsArray($logs);
    }

    public function test_get_available_logs_has_name_and_title(): void
    {
        $logs = ActivityLog::getAvailableLogs();

        foreach ($logs as $logName) {
            $this->assertIsString($logName);
            $title = ActivityLog::getLogTitle($logName);
            $this->assertNotEmpty($title);
        }
    }

    public function test_get_available_logs_includes_email_errors(): void
    {
        $logs = ActivityLog::getAvailableLogs();
        // getAvailableLogs returns array of strings, not objects with name property
        // The original test code: $names = array_column($logs, 'name'); implies $logs is array of objects/arrays?
        // But ActivityLog::getAvailableLogs() returns array<string>.
        // Let's check the implementation again.
        // public static function getAvailableLogs(bool $checkExisting = true): array { ... return array_values(array_unique($availableLogs)); }
        // So it returns ['log1', 'log2'].

        $this->assertContains(ActivityLog::NAME_EMAILS_SENDING, $logs);
    }

    // ===== Activity logging for conversations tests =====

    public function test_activity_log_can_store_conversation_id(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->for($this->mailbox)->create([
            'customer_id' => $customer->id,
        ]);

        $log = ActivityLog::factory()->create([
            'subject_type' => Conversation::class,
            'subject_id' => $conversation->id,
            'causer_id' => $this->user->id,
            'causer_type' => User::class,
            'log_name' => 'conversations',
            'description' => 1,
        ]);

        $this->assertEquals($conversation->id, $log->subject_id);
    }

    public function test_activity_log_can_store_properties(): void
    {
        $log = ActivityLog::factory()->create([
            'properties' => [
                'old_status' => 1,
                'new_status' => 3,
            ],
        ]);

        $this->assertEquals(1, $log->properties['old_status']);
        $this->assertEquals(3, $log->properties['new_status']);
    }

    public function test_activity_log_can_retrieve_user_causer(): void
    {
        $log = ActivityLog::factory()->create([
            'causer_id' => $this->user->id,
            'causer_type' => User::class,
        ]);

        $this->assertInstanceOf(User::class, $log->causer);
        $this->assertEquals($this->user->id, $log->causer->id);
    }
}
