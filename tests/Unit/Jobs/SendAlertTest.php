<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendAlert;
use App\Models\SendLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendAlertTest extends TestCase
{
    use RefreshDatabase;
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

    #[Test]
    public function job_sends_to_all_active_admins(): void
    {
        Mail::fake();
        Log::spy();

        User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'email' => 'admin1@example.com',
        ]);
        User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'email' => 'admin2@example.com',
        ]);

        $job = new SendAlert('Critical alert', 'System Alert');
        $job->handle();

        $this->assertEquals(2, SendLog::where('mail_type', SendLog::MAIL_TYPE_ALERT)->count());
    }

    #[Test]
    public function job_skips_inactive_admins(): void
    {
        Mail::fake();
        Log::spy();

        User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'email' => 'active@example.com',
        ]);
        User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_DELETED,
            'email' => 'inactive@example.com',
        ]);

        $job = new SendAlert('Test alert');
        $job->handle();

        $this->assertEquals(1, SendLog::where('mail_type', SendLog::MAIL_TYPE_ALERT)->count());
        $this->assertDatabaseHas('send_logs', ['email' => 'active@example.com']);
        $this->assertDatabaseMissing('send_logs', ['email' => 'inactive@example.com']);
    }

    #[Test]
    public function job_skips_non_admin_users(): void
    {
        Mail::fake();
        Log::spy();

        User::factory()->create([
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
            'email' => 'user@example.com',
        ]);
        User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'email' => 'admin@example.com',
        ]);

        $job = new SendAlert('Test alert');
        $job->handle();

        $this->assertEquals(1, SendLog::where('mail_type', SendLog::MAIL_TYPE_ALERT)->count());
        $this->assertDatabaseHas('send_logs', ['email' => 'admin@example.com']);
        $this->assertDatabaseMissing('send_logs', ['email' => 'user@example.com']);
    }

    #[Test]
    public function job_creates_send_log_on_success(): void
    {
        Mail::fake();
        Log::spy();

        User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'email' => 'admin@example.com',
        ]);

        $job = new SendAlert('Test alert', 'Alert Title');
        $job->handle();

        $this->assertDatabaseHas('send_logs', [
            'email' => 'admin@example.com',
            'mail_type' => SendLog::MAIL_TYPE_ALERT,
            'status' => SendLog::STATUS_ACCEPTED,
        ]);
    }

    #[Test]
    public function job_logs_info_when_sending(): void
    {
        Mail::fake();
        Log::spy();

        User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'email' => 'admin@example.com',
        ]);

        $job = new SendAlert('Important message', 'Critical');
        $job->handle();

        Log::shouldHaveReceived('info')
            ->with('Sending alert email', \Mockery::type('array'))
            ->once();
    }

    #[Test]
    public function job_can_be_queued(): void
    {
        Queue::fake();

        SendAlert::dispatch('Test alert', 'Title');

        Queue::assertPushed(SendAlert::class);
    }

    #[Test]
    public function job_handles_no_admins_gracefully(): void
    {
        Mail::fake();
        Log::spy();

        $job = new SendAlert('Test alert');
        $job->handle();

        $this->assertEquals(0, SendLog::where('mail_type', SendLog::MAIL_TYPE_ALERT)->count());
    }

    #[Test]
    public function job_processes_multiple_recipients(): void
    {
        Mail::fake();
        Log::spy();

        User::factory()->count(3)->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $job = new SendAlert('Multi-recipient alert');
        $job->handle();

        $this->assertEquals(3, SendLog::where('mail_type', SendLog::MAIL_TYPE_ALERT)->count());
    }

    #[Test]
    public function job_stores_null_for_thread_and_user_ids(): void
    {
        Mail::fake();
        Log::spy();

        User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'email' => 'admin@example.com',
        ]);

        $job = new SendAlert('Test alert');
        $job->handle();

        $this->assertDatabaseHas('send_logs', [
            'email' => 'admin@example.com',
            'thread_id' => null,
            'user_id' => null,
            'message_id' => null,
        ]);
    }
}
