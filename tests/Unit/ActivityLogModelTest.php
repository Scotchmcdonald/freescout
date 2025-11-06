<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\ActivityLog;
use Tests\TestCase;

class ActivityLogModelTest extends TestCase
{
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
}
