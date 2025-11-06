<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\SendLog;
use Tests\TestCase;

class SendLogModelTest extends TestCase
{
    public function test_model_can_be_instantiated(): void
    {
        $log = new SendLog();
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
}
