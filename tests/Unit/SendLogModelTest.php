<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\SendLog;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendLogModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_can_be_instantiated(): void
    {
        $log = new SendLog;
        $this->assertInstanceOf(SendLog::class, $log);
    }

    public function test_model_has_fillable_attributes(): void
    {
        $log = new SendLog([
            'thread_id' => 1,
            'message_id' => 'message-id-123',
            'email' => 'test@example.com',
            'status' => 1,
            'customer_id' => 456,
            'status_message' => 'Sent successfully',
        ]);

        $this->assertEquals(1, $log->thread_id);
        $this->assertEquals('message-id-123', $log->message_id);
        $this->assertEquals('test@example.com', $log->email);
        $this->assertEquals(1, $log->status);
        $this->assertEquals(456, $log->customer_id);
        $this->assertEquals('Sent successfully', $log->status_message);
    }

    public function test_is_sent_returns_true_for_sent_status(): void
    {
        $log = SendLog::factory()->make(['status' => 1]);

        $this->assertTrue($log->isSent());
    }

    public function test_is_sent_returns_false_for_non_sent_status(): void
    {
        $log = SendLog::factory()->make(['status' => 2]);

        $this->assertFalse($log->isSent());
    }

    public function test_is_failed_returns_true_for_failed_status(): void
    {
        $log = SendLog::factory()->make(['status' => 2]);

        $this->assertTrue($log->isFailed());
    }

    public function test_is_failed_returns_false_for_non_failed_status(): void
    {
        $log = SendLog::factory()->make(['status' => 1]);

        $this->assertFalse($log->isFailed());
    }

    public function test_mail_type_field_exists(): void
    {
        $log = SendLog::factory()->create(['mail_type' => 1]);

        $this->assertEquals(1, $log->mail_type);
        $this->assertDatabaseHas('send_logs', [
            'id' => $log->id,
            'mail_type' => 1,
        ]);
    }

    public function test_subject_field_exists(): void
    {
        $log = SendLog::factory()->create(['subject' => 'Test Subject']);

        $this->assertEquals('Test Subject', $log->subject);
        $this->assertDatabaseHas('send_logs', [
            'id' => $log->id,
            'subject' => 'Test Subject',
        ]);
    }

    public function test_belongs_to_thread(): void
    {
        $thread = Thread::factory()->create();
        $log = SendLog::factory()->for($thread)->create();

        $this->assertInstanceOf(Thread::class, $log->thread);
        $this->assertEquals($thread->id, $log->thread->id);
    }

    public function test_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $log = SendLog::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $log->customer);
        $this->assertEquals($customer->id, $log->customer->id);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $log = SendLog::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertEquals($user->id, $log->user->id);
    }

    public function test_send_log_with_null_customer_id(): void
    {
        $log = SendLog::factory()->create(['customer_id' => null]);

        $this->assertNull($log->customer_id);
        $this->assertNull($log->customer);
    }

    public function test_send_log_with_null_user_id(): void
    {
        $log = SendLog::factory()->create(['user_id' => null]);

        $this->assertNull($log->user_id);
        $this->assertNull($log->user);
    }

    public function test_send_log_with_null_status_message(): void
    {
        $log = SendLog::factory()->create(['status_message' => null]);

        $this->assertNull($log->status_message);
    }

    public function test_send_log_with_status_message(): void
    {
        $log = SendLog::factory()->create(['status_message' => 'Email sent successfully']);

        $this->assertEquals('Email sent successfully', $log->status_message);
    }

    public function test_send_log_mail_type_values(): void
    {
        // Test different mail types
        $log1 = SendLog::factory()->create(['mail_type' => 1]);
        $this->assertEquals(1, $log1->mail_type);

        $log2 = SendLog::factory()->create(['mail_type' => 2]);
        $this->assertEquals(2, $log2->mail_type);

        $log3 = SendLog::factory()->create(['mail_type' => 3]);
        $this->assertEquals(3, $log3->mail_type);
    }

    public function test_send_log_status_values(): void
    {
        // Test status 1 (sent)
        $sentLog = SendLog::factory()->create(['status' => 1]);
        $this->assertTrue($sentLog->isSent());
        $this->assertFalse($sentLog->isFailed());

        // Test status 2 (failed)
        $failedLog = SendLog::factory()->create(['status' => 2]);
        $this->assertFalse($failedLog->isSent());
        $this->assertTrue($failedLog->isFailed());
    }

    public function test_send_log_with_smtp_queue_id(): void
    {
        $queueId = 'smtp-queue-'.uniqid();
        $log = SendLog::factory()->create(['smtp_queue_id' => $queueId]);

        $this->assertEquals($queueId, $log->smtp_queue_id);
    }

    public function test_send_log_with_null_smtp_queue_id(): void
    {
        $log = SendLog::factory()->create(['smtp_queue_id' => null]);

        $this->assertNull($log->smtp_queue_id);
    }

    public function test_multiple_send_logs_for_same_thread(): void
    {
        $thread = Thread::factory()->create();

        SendLog::factory()->count(3)->create(['thread_id' => $thread->id]);

        $sendLogs = SendLog::where('thread_id', $thread->id)->get();

        $this->assertCount(3, $sendLogs);
        $this->assertTrue($sendLogs->every(fn ($log) => $log->thread_id === $thread->id));
    }

    public function test_created_at_and_updated_at_timestamps(): void
    {
        $log = SendLog::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $log->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $log->updated_at);
    }
}
