<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendAlert;
use Tests\TestCase;

class SendAlertTest extends TestCase
{
    public function test_job_has_required_properties(): void
    {
        $text = 'Test alert message';
        $title = 'Test Alert';

        $job = new SendAlert($text, $title);

        $this->assertEquals($text, $job->text);
        $this->assertEquals($title, $job->title);
    }

    public function test_job_has_timeout_property(): void
    {
        $text = 'Test alert message';
        $title = 'Test Alert';

        $job = new SendAlert($text, $title);

        $this->assertEquals(120, $job->timeout);
    }

    public function test_handle_method_exists(): void
    {
        $text = 'Test alert message';
        $title = 'Test Alert';

        $job = new SendAlert($text, $title);

        $this->assertTrue(method_exists($job, 'handle'));
    }

    public function test_job_can_be_created_without_title(): void
    {
        $text = 'Test alert message';

        $job = new SendAlert($text);

        $this->assertEquals($text, $job->text);
        $this->assertEquals('', $job->title);
    }
}
