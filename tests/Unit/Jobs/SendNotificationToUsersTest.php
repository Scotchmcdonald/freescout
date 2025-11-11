<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendNotificationToUsers;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SendNotificationToUsersTest extends TestCase
{
    public function test_job_has_required_properties(): void
    {
        $users = new Collection([User::factory()->make()]);
        $conversation = Conversation::factory()->make(['id' => 1]);
        $threads = new Collection([Thread::factory()->make(['id' => 2])]);

        $job = new SendNotificationToUsers($users, $conversation, $threads);

        $this->assertSame($users, $job->users);
        $this->assertSame($conversation, $job->conversation);
        $this->assertSame($threads, $job->threads);
    }

    public function test_job_has_timeout_property(): void
    {
        $users = new Collection([User::factory()->make()]);
        $conversation = Conversation::factory()->make(['id' => 1]);
        $threads = new Collection([Thread::factory()->make(['id' => 2])]);

        $job = new SendNotificationToUsers($users, $conversation, $threads);

        $this->assertEquals(120, $job->timeout);
    }

    public function test_job_has_tries_property(): void
    {
        $users = new Collection([User::factory()->make()]);
        $conversation = Conversation::factory()->make(['id' => 1]);
        $threads = new Collection([Thread::factory()->make(['id' => 2])]);

        $job = new SendNotificationToUsers($users, $conversation, $threads);

        $this->assertEquals(168, $job->tries);
    }

    public function test_handle_method_exists(): void
    {
        $users = new Collection([User::factory()->make()]);
        $conversation = Conversation::factory()->make(['id' => 1]);
        $threads = new Collection([Thread::factory()->make(['id' => 2])]);

        $job = new SendNotificationToUsers($users, $conversation, $threads);

        $this->assertTrue(method_exists($job, 'handle'));
    }

    public function test_failed_method_exists(): void
    {
        $users = new Collection([User::factory()->make()]);
        $conversation = Conversation::factory()->make(['id' => 1]);
        $threads = new Collection([Thread::factory()->make(['id' => 2])]);

        $job = new SendNotificationToUsers($users, $conversation, $threads);

        $this->assertTrue(method_exists($job, 'failed'));
    }
}
