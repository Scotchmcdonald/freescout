<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendEmailReplyError;
use App\Models\Mailbox;
use App\Models\User;
use Tests\TestCase;

class SendEmailReplyErrorTest extends TestCase
{
    public function test_job_has_required_properties(): void
    {
        $from = 'test@example.com';
        $user = User::factory()->make(['id' => 1]);
        $mailbox = Mailbox::factory()->make(['id' => 2]);

        $job = new SendEmailReplyError($from, $user, $mailbox);

        $this->assertEquals($from, $job->from);
        $this->assertSame($user, $job->user);
        $this->assertSame($mailbox, $job->mailbox);
    }

    public function test_job_has_timeout_property(): void
    {
        $from = 'test@example.com';
        $user = User::factory()->make(['id' => 1]);
        $mailbox = Mailbox::factory()->make(['id' => 2]);

        $job = new SendEmailReplyError($from, $user, $mailbox);

        $this->assertEquals(120, $job->timeout);
    }

    public function test_handle_method_exists(): void
    {
        $from = 'test@example.com';
        $user = User::factory()->make(['id' => 1]);
        $mailbox = Mailbox::factory()->make(['id' => 2]);

        $job = new SendEmailReplyError($from, $user, $mailbox);

        $this->assertTrue(method_exists($job, 'handle'));
    }
}
