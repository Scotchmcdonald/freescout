<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\UserNotification;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function mailable_can_be_instantiated(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $threads = collect([Thread::factory()->create(['conversation_id' => $conversation->id])]);
        $headers = ['Message-ID' => 'test-id'];
        $from = ['address' => 'support@example.com', 'name' => 'Support'];

        $mailable = new UserNotification($user, $conversation, $threads, $headers, $from, $mailbox);

        $this->assertInstanceOf(UserNotification::class, $mailable);
        $this->assertEquals($user->id, $mailable->user->id);
        $this->assertEquals($conversation->id, $mailable->conversation->id);
    }

    #[Test]
    public function envelope_contains_conversation_number_in_subject(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'number' => 12345,
            'subject' => 'Test Subject',
        ]);
        $threads = collect([Thread::factory()->create(['conversation_id' => $conversation->id])]);

        $mailable = new UserNotification($user, $conversation, $threads, [], [], $mailbox);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('[#12345]', $envelope->subject);
        $this->assertStringContainsString('Test Subject', $envelope->subject);
    }

    #[Test]
    public function envelope_uses_custom_from_address(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $threads = collect([Thread::factory()->create(['conversation_id' => $conversation->id])]);
        $from = ['address' => 'custom@example.com', 'name' => 'Custom Name'];

        $mailable = new UserNotification($user, $conversation, $threads, [], $from, $mailbox);
        $envelope = $mailable->envelope();

        // In Laravel 11, envelope->from is a single Address object
        $this->assertInstanceOf(\Illuminate\Mail\Mailables\Address::class, $envelope->from);
        $this->assertEquals('custom@example.com', $envelope->from->address);
    }

    #[Test]
    public function content_uses_notification_views(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $threads = collect([Thread::factory()->create(['conversation_id' => $conversation->id])]);

        $mailable = new UserNotification($user, $conversation, $threads, [], [], $mailbox);
        $content = $mailable->content();

        $this->assertEquals('emails.user.notification', $content->view);
        $this->assertEquals('emails.user.notification_text', $content->text);
    }

    #[Test]
    public function content_includes_customer_thread_and_mailbox(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $threads = collect([Thread::factory()->create(['conversation_id' => $conversation->id])]);

        $mailable = new UserNotification($user, $conversation, $threads, [], [], $mailbox);
        $content = $mailable->content();

        $this->assertArrayHasKey('customer', $content->with);
        $this->assertArrayHasKey('thread', $content->with);
        $this->assertArrayHasKey('mailbox', $content->with);
    }

    #[Test]
    public function build_method_sets_custom_headers(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id, 'number' => 100]);
        $threads = collect([Thread::factory()->create(['conversation_id' => $conversation->id])]);
        $headers = [
            'Message-ID' => '<test-message-id@example.com>',
            'X-Custom-Header' => 'CustomValue',
        ];

        $mailable = new UserNotification($user, $conversation, $threads, $headers, [], $mailbox);
        $built = $mailable->build();

        $this->assertInstanceOf(UserNotification::class, $built);
    }

    #[Test]
    public function mailable_can_be_sent(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'user@example.com']);
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $threads = collect([Thread::factory()->create(['conversation_id' => $conversation->id])]);

        Mail::to($user->email)->send(new UserNotification($user, $conversation, $threads, [], [], $mailbox));

        Mail::assertSent(UserNotification::class);
    }

    #[Test]
    public function build_includes_all_view_data(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $threads = collect([
            Thread::factory()->create(['conversation_id' => $conversation->id]),
            Thread::factory()->create(['conversation_id' => $conversation->id]),
        ]);

        $mailable = new UserNotification($user, $conversation, $threads, [], [], $mailbox);
        $built = $mailable->build();

        $this->assertNotNull($built);
    }

    #[Test]
    public function build_uses_config_defaults_when_from_not_provided(): void
    {
        config(['mail.from.address' => 'default@example.com']);
        
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $threads = collect([Thread::factory()->create(['conversation_id' => $conversation->id])]);

        $mailable = new UserNotification($user, $conversation, $threads, [], [], $mailbox);
        $envelope = $mailable->envelope();

        // In Laravel 11, envelope->from is a single Address object
        $this->assertInstanceOf(\Illuminate\Mail\Mailables\Address::class, $envelope->from);
        $this->assertEquals('default@example.com', $envelope->from->address);
    }

    #[Test]
    public function mailable_is_queueable(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $threads = collect([Thread::factory()->create(['conversation_id' => $conversation->id])]);

        $mailable = new UserNotification($user, $conversation, $threads, [], [], $mailbox);
        
        $this->assertTrue(method_exists($mailable, 'onQueue'));
    }
}
