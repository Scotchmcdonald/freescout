<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Customer;
use App\Models\SendLog;
use App\Models\Thread;
use App\Models\User;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for SendLog Model
 * Following TESTING_GUIDE.md - using test_ prefix, UnitTestCase base class
 */
class SendLogTest extends UnitTestCase
{
    // ===== RELATIONSHIP TESTS =====

    public function test_send_log_belongs_to_thread(): void
    {
        $thread = Thread::factory()->create();
        $sendLog = SendLog::factory()->create(['thread_id' => $thread->id]);

        $this->assertInstanceOf(Thread::class, $sendLog->thread);
        $this->assertEquals($thread->id, $sendLog->thread->id);
    }

    public function test_send_log_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $sendLog = SendLog::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $sendLog->customer);
        $this->assertEquals($customer->id, $sendLog->customer->id);
    }

    public function test_send_log_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $sendLog = SendLog::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $sendLog->user);
        $this->assertEquals($user->id, $sendLog->user->id);
    }

    public function test_send_log_customer_can_be_null(): void
    {
        $sendLog = SendLog::factory()->create(['customer_id' => null]);

        $this->assertNull($sendLog->customer);
    }

    public function test_send_log_user_can_be_null(): void
    {
        $sendLog = SendLog::factory()->create(['user_id' => null]);

        $this->assertNull($sendLog->user);
    }

    // ===== STATUS CONSTANT TESTS =====

    public function test_status_accepted_constant_exists(): void
    {
        $this->assertEquals(1, SendLog::STATUS_ACCEPTED);
    }

    public function test_status_send_error_constant_exists(): void
    {
        $this->assertEquals(2, SendLog::STATUS_SEND_ERROR);
    }

    public function test_status_delivery_success_constant_exists(): void
    {
        $this->assertEquals(4, SendLog::STATUS_DELIVERY_SUCCESS);
    }

    public function test_status_delivery_error_constant_exists(): void
    {
        $this->assertEquals(5, SendLog::STATUS_DELIVERY_ERROR);
    }

    public function test_status_opened_constant_exists(): void
    {
        $this->assertEquals(6, SendLog::STATUS_OPENED);
    }

    public function test_status_clicked_constant_exists(): void
    {
        $this->assertEquals(7, SendLog::STATUS_CLICKED);
    }

    public function test_status_sent_is_alias_for_accepted(): void
    {
        $this->assertEquals(SendLog::STATUS_ACCEPTED, SendLog::STATUS_SENT);
    }

    // ===== MAIL TYPE CONSTANT TESTS =====

    public function test_mail_type_email_to_customer_constant_exists(): void
    {
        $this->assertEquals(1, SendLog::MAIL_TYPE_EMAIL_TO_CUSTOMER);
    }

    public function test_mail_type_user_notification_constant_exists(): void
    {
        $this->assertEquals(2, SendLog::MAIL_TYPE_USER_NOTIFICATION);
    }

    public function test_mail_type_auto_reply_constant_exists(): void
    {
        $this->assertEquals(3, SendLog::MAIL_TYPE_AUTO_REPLY);
    }

    // ===== IS_SENT METHOD TESTS =====

    public function test_is_sent_returns_true_for_accepted_status(): void
    {
        $sendLog = SendLog::factory()->create(['status' => SendLog::STATUS_ACCEPTED]);

        $this->assertTrue($sendLog->isSent());
    }

    public function test_is_sent_returns_true_for_delivery_success_status(): void
    {
        $sendLog = SendLog::factory()->create(['status' => SendLog::STATUS_DELIVERY_SUCCESS]);

        $this->assertTrue($sendLog->isSent());
    }

    public function test_is_sent_returns_false_for_send_error_status(): void
    {
        $sendLog = SendLog::factory()->create(['status' => SendLog::STATUS_SEND_ERROR]);

        $this->assertFalse($sendLog->isSent());
    }

    public function test_is_sent_returns_false_for_delivery_error_status(): void
    {
        $sendLog = SendLog::factory()->create(['status' => SendLog::STATUS_DELIVERY_ERROR]);

        $this->assertFalse($sendLog->isSent());
    }

    // ===== IS_FAILED METHOD TESTS =====

    public function test_is_failed_returns_true_for_send_error_status(): void
    {
        $sendLog = SendLog::factory()->create(['status' => SendLog::STATUS_SEND_ERROR]);

        $this->assertTrue($sendLog->isFailed());
    }

    public function test_is_failed_returns_true_for_delivery_error_status(): void
    {
        $sendLog = SendLog::factory()->create(['status' => SendLog::STATUS_DELIVERY_ERROR]);

        $this->assertTrue($sendLog->isFailed());
    }

    public function test_is_failed_returns_false_for_accepted_status(): void
    {
        $sendLog = SendLog::factory()->create(['status' => SendLog::STATUS_ACCEPTED]);

        $this->assertFalse($sendLog->isFailed());
    }

    public function test_is_failed_returns_false_for_delivery_success_status(): void
    {
        $sendLog = SendLog::factory()->create(['status' => SendLog::STATUS_DELIVERY_SUCCESS]);

        $this->assertFalse($sendLog->isFailed());
    }

    // ===== WAS_OPENED METHOD TESTS =====

    public function test_was_opened_returns_true_when_opened_at_is_set(): void
    {
        $sendLog = SendLog::factory()->create(['opened_at' => now()]);

        $this->assertTrue($sendLog->wasOpened());
    }

    public function test_was_opened_returns_false_when_opened_at_is_null(): void
    {
        $sendLog = SendLog::factory()->create(['opened_at' => null]);

        $this->assertFalse($sendLog->wasOpened());
    }

    // ===== WAS_CLICKED METHOD TESTS =====

    public function test_was_clicked_returns_true_when_clicked_at_is_set(): void
    {
        $sendLog = SendLog::factory()->create(['clicked_at' => now()]);

        $this->assertTrue($sendLog->wasClicked());
    }

    public function test_was_clicked_returns_false_when_clicked_at_is_null(): void
    {
        $sendLog = SendLog::factory()->create(['clicked_at' => null]);

        $this->assertFalse($sendLog->wasClicked());
    }

    // ===== ATTRIBUTE TESTS =====

    public function test_send_log_has_message_id_attribute(): void
    {
        $sendLog = SendLog::factory()->create(['message_id' => '<unique@example.com>']);

        $this->assertEquals('<unique@example.com>', $sendLog->message_id);
    }

    public function test_send_log_has_status_message_attribute(): void
    {
        $sendLog = SendLog::factory()->create(['status_message' => 'Delivered successfully']);

        $this->assertEquals('Delivered successfully', $sendLog->status_message);
    }

    public function test_send_log_has_opens_count_attribute(): void
    {
        $sendLog = SendLog::factory()->create(['opens' => 5]);

        $this->assertEquals(5, $sendLog->opens);
    }

    public function test_send_log_has_clicks_count_attribute(): void
    {
        $sendLog = SendLog::factory()->create(['clicks' => 3]);

        $this->assertEquals(3, $sendLog->clicks);
    }

    public function test_send_log_has_meta_attribute(): void
    {
        $meta = ['key' => 'value', 'foo' => 'bar'];
        $sendLog = SendLog::factory()->create(['meta' => $meta]);

        $this->assertEquals($meta, $sendLog->meta);
    }

    // ===== TIMESTAMP TESTS =====

    public function test_send_log_has_created_at_timestamp(): void
    {
        $sendLog = SendLog::factory()->create();

        $this->assertNotNull($sendLog->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $sendLog->created_at);
    }

    public function test_send_log_has_updated_at_timestamp(): void
    {
        $sendLog = SendLog::factory()->create();

        $this->assertNotNull($sendLog->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $sendLog->updated_at);
    }

    // ===== QUERY TESTS =====

    public function test_can_query_send_logs_by_status(): void
    {
        SendLog::factory()->create(['status' => SendLog::STATUS_ACCEPTED]);
        SendLog::factory()->create(['status' => SendLog::STATUS_SEND_ERROR]);

        $acceptedLogs = SendLog::where('status', SendLog::STATUS_ACCEPTED)->get();

        $this->assertCount(1, $acceptedLogs);
    }

    public function test_can_query_send_logs_by_email(): void
    {
        SendLog::factory()->create(['email' => 'test1@example.com']);
        SendLog::factory()->create(['email' => 'test2@example.com']);

        $logs = SendLog::where('email', 'test1@example.com')->get();

        $this->assertCount(1, $logs);
    }

    public function test_can_query_send_logs_by_thread(): void
    {
        $thread = Thread::factory()->create();
        SendLog::factory()->count(3)->create(['thread_id' => $thread->id]);
        SendLog::factory()->create(); // Different thread

        $logs = SendLog::where('thread_id', $thread->id)->get();

        $this->assertCount(3, $logs);
    }

    // ===== EDGE CASES =====

    public function test_send_log_with_null_status_message(): void
    {
        $sendLog = SendLog::factory()->create(['status_message' => null]);

        $this->assertNull($sendLog->status_message);
    }

    public function test_send_log_with_null_message_id(): void
    {
        $sendLog = SendLog::factory()->create(['message_id' => null]);

        $this->assertNull($sendLog->message_id);
    }

    public function test_send_log_with_zero_opens(): void
    {
        $sendLog = SendLog::factory()->create(['opens' => 0]);

        $this->assertEquals(0, $sendLog->opens);
        $this->assertFalse($sendLog->wasOpened());
    }

    public function test_send_log_with_zero_clicks(): void
    {
        $sendLog = SendLog::factory()->create(['clicks' => 0]);

        $this->assertEquals(0, $sendLog->clicks);
        $this->assertFalse($sendLog->wasClicked());
    }

    public function test_send_log_can_be_updated(): void
    {
        $sendLog = SendLog::factory()->create(['status' => SendLog::STATUS_ACCEPTED]);

        $sendLog->update(['status' => SendLog::STATUS_DELIVERY_SUCCESS]);

        $this->assertEquals(SendLog::STATUS_DELIVERY_SUCCESS, $sendLog->fresh()->status);
    }

    public function test_send_log_can_be_deleted(): void
    {
        $sendLog = SendLog::factory()->create();
        $id = $sendLog->id;

        $sendLog->delete();

        $this->assertDatabaseMissing('send_logs', ['id' => $id]);
    }

    public function test_multiple_send_logs_can_exist_for_same_thread(): void
    {
        $thread = Thread::factory()->create();

        SendLog::factory()->count(5)->create(['thread_id' => $thread->id]);

        $this->assertCount(5, $thread->sendLogs);
    }

    public function test_send_log_with_empty_meta_array(): void
    {
        $sendLog = SendLog::factory()->create(['meta' => []]);

        $this->assertEquals([], $sendLog->meta);
    }

    public function test_send_log_with_null_meta(): void
    {
        $sendLog = SendLog::factory()->create(['meta' => null]);

        $this->assertNull($sendLog->meta);
    }

    public function test_send_log_email_validation(): void
    {
        $sendLog = SendLog::factory()->create(['email' => 'valid@example.com']);

        $this->assertMatchesRegularExpression('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $sendLog->email);
    }

    public function test_send_log_with_very_long_email(): void
    {
        $longEmail = str_repeat('a', 100).'@example.com';
        $sendLog = SendLog::factory()->create(['email' => $longEmail]);

        $this->assertEquals($longEmail, $sendLog->email);
    }

    public function test_send_log_status_can_transition_from_accepted_to_opened(): void
    {
        $sendLog = SendLog::factory()->create([
            'status' => SendLog::STATUS_ACCEPTED,
            'opened_at' => null,
        ]);

        $sendLog->update([
            'status' => SendLog::STATUS_OPENED,
            'opened_at' => now(),
            'opens' => 1,
        ]);

        $this->assertEquals(SendLog::STATUS_OPENED, $sendLog->fresh()->status);
        $this->assertTrue($sendLog->fresh()->wasOpened());
    }

    public function test_send_log_can_track_multiple_opens(): void
    {
        $sendLog = SendLog::factory()->create(['opens' => 0]);

        $sendLog->increment('opens');
        $sendLog->increment('opens');
        $sendLog->increment('opens');

        $this->assertEquals(3, $sendLog->fresh()->opens);
    }

    public function test_send_log_can_track_multiple_clicks(): void
    {
        $sendLog = SendLog::factory()->create(['clicks' => 0]);

        $sendLog->increment('clicks');
        $sendLog->increment('clicks');

        $this->assertEquals(2, $sendLog->fresh()->clicks);
    }
}
