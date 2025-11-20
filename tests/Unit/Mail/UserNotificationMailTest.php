<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\UserNotification;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Collection;
use Tests\UnitTestCase;

class UserNotificationMailTest extends UnitTestCase
{
    public function test_envelope_sets_correct_subject_with_conversation_number(): void
    {
        $user = User::factory()->create(['id' => 1]);
        $mailbox = Mailbox::factory()->create(['id' => 1]);
        $conversation = Conversation::factory()->create([
            'number' => 123,
            'subject' => 'Test Subject',
        ]);
        $threads = collect([Thread::factory()->create()]);
        
        $mailable = new UserNotification($user, $conversation, $threads, [], [], $mailbox);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('[#123]', $envelope->subject);
        $this->assertStringContainsString('Test Subject', $envelope->subject);
    }

    public function test_envelope_uses_custom_from_address_when_provided(): void
    {
        $user = User::factory()->create(['id' => 1]);
        $mailbox = Mailbox::factory()->create(['id' => 1]);
        $conversation = Conversation::factory()->create(['number' => 123]);
        $threads = collect([Thread::factory()->create()]);
        $fromAddress = ['address' => 'custom@example.com', 'name' => 'Custom Name'];
        
        $mailable = new UserNotification($user, $conversation, $threads, [], $fromAddress, $mailbox);
        $envelope = $mailable->envelope();

        $this->assertEquals('custom@example.com', $envelope->from[0]->address);
    }

    public function test_envelope_uses_config_from_address_when_not_provided(): void
    {
        config(['mail.from.address' => 'default@example.com']);
        $user = User::factory()->create(['id' => 1]);
        $mailbox = Mailbox::factory()->create(['id' => 1]);
        $conversation = Conversation::factory()->create(['number' => 123]);
        $threads = collect([Thread::factory()->create()]);
        
        $mailable = new UserNotification($user, $conversation, $threads, [], [], $mailbox);
        $envelope = $mailable->envelope();

        $this->assertEquals('default@example.com', $envelope->from[0]->address);
    }

    public function test_content_uses_correct_view(): void
    {
        $user = User::factory()->create(['id' => 1]);
        $mailbox = Mailbox::factory()->create(['id' => 1]);
        $customer = Customer::factory()->create(['id' => 1]);
        $conversation = Conversation::factory()->create([
            'number' => 123,
            'customer_id' => $customer->id,
        ]);
        $conversation->setRelation('customer', $customer);
        $threads = collect([Thread::factory()->create()]);
        
        $mailable = new UserNotification($user, $conversation, $threads, [], [], $mailbox);
        $content = $mailable->content();

        $this->assertEquals('emails.user.notification', $content->view);
    }

    public function test_content_uses_correct_text_view(): void
    {
        $user = User::factory()->create(['id' => 1]);
        $mailbox = Mailbox::factory()->create(['id' => 1]);
        $customer = Customer::factory()->create(['id' => 1]);
        $conversation = Conversation::factory()->create([
            'number' => 123,
            'customer_id' => $customer->id,
        ]);
        $conversation->setRelation('customer', $customer);
        $threads = collect([Thread::factory()->create()]);
        
        $mailable = new UserNotification($user, $conversation, $threads, [], [], $mailbox);
        $content = $mailable->content();

        $this->assertEquals('emails.user.notification_text', $content->text);
    }

    public function test_content_includes_customer_data(): void
    {
        $user = User::factory()->create(['id' => 1]);
        $mailbox = Mailbox::factory()->create(['id' => 1]);
        $customer = Customer::factory()->create(['id' => 1]);
        $conversation = Conversation::factory()->create([
            'number' => 123,
            'customer_id' => $customer->id,
        ]);
        $conversation->setRelation('customer', $customer);
        $threads = collect([Thread::factory()->create()]);
        
        $mailable = new UserNotification($user, $conversation, $threads, [], [], $mailbox);
        $content = $mailable->content();

        $this->assertArrayHasKey('customer', $content->with);
        $this->assertInstanceOf(Customer::class, $content->with['customer']);
    }

    public function test_content_includes_thread_data(): void
    {
        $user = User::factory()->create(['id' => 1]);
        $mailbox = Mailbox::factory()->create(['id' => 1]);
        $customer = Customer::factory()->create(['id' => 1]);
        $conversation = Conversation::factory()->create([
            'number' => 123,
            'customer_id' => $customer->id,
        ]);
        $conversation->setRelation('customer', $customer);
        $thread = Thread::factory()->create(['id' => 5]);
        $threads = collect([$thread]);
        
        $mailable = new UserNotification($user, $conversation, $threads, [], [], $mailbox);
        $content = $mailable->content();

        $this->assertArrayHasKey('thread', $content->with);
        $this->assertInstanceOf(Thread::class, $content->with['thread']);
        $this->assertEquals(5, $content->with['thread']->id);
    }

    public function test_content_includes_mailbox_data(): void
    {
        $user = User::factory()->create(['id' => 1]);
        $mailbox = Mailbox::factory()->create(['id' => 1, 'name' => 'Support']);
        $customer = Customer::factory()->create(['id' => 1]);
        $conversation = Conversation::factory()->create([
            'number' => 123,
            'customer_id' => $customer->id,
        ]);
        $conversation->setRelation('customer', $customer);
        $threads = collect([Thread::factory()->create()]);
        
        $mailable = new UserNotification($user, $conversation, $threads, [], [], $mailbox);
        $content = $mailable->content();

        $this->assertArrayHasKey('mailbox', $content->with);
        $this->assertInstanceOf(Mailbox::class, $content->with['mailbox']);
        $this->assertEquals('Support', $content->with['mailbox']->name);
    }

    public function test_mailable_stores_all_properties(): void
    {
        $user = User::factory()->create(['id' => 1]);
        $mailbox = Mailbox::factory()->create(['id' => 2]);
        $conversation = Conversation::factory()->create(['number' => 123]);
        $threads = collect([Thread::factory()->create()]);
        $headers = ['X-Custom' => 'value'];
        $fromAddress = ['address' => 'from@example.com'];
        
        $mailable = new UserNotification($user, $conversation, $threads, $headers, $fromAddress, $mailbox);

        $this->assertInstanceOf(User::class, $mailable->user);
        $this->assertInstanceOf(Conversation::class, $mailable->conversation);
        $this->assertInstanceOf(Collection::class, $mailable->threads);
        $this->assertInstanceOf(Mailbox::class, $mailable->mailbox);
        $this->assertEquals($headers, $mailable->headers);
        $this->assertEquals($fromAddress, $mailable->fromAddress);
    }

    public function test_build_method_returns_self(): void
    {
        $user = User::factory()->create(['id' => 1]);
        $mailbox = Mailbox::factory()->create(['id' => 1]);
        $customer = Customer::factory()->create(['id' => 1]);
        $conversation = Conversation::factory()->create([
            'number' => 123,
            'customer_id' => $customer->id,
        ]);
        $conversation->setRelation('customer', $customer);
        $threads = collect([Thread::factory()->create()]);
        
        $mailable = new UserNotification($user, $conversation, $threads, [], [], $mailbox);
        $result = $mailable->build();

        $this->assertInstanceOf(UserNotification::class, $result);
        $this->assertSame($mailable, $result);
    }

    public function test_mailable_can_be_constructed(): void
    {
        $user = User::factory()->create(['id' => 1]);
        $mailbox = Mailbox::factory()->create(['id' => 1]);
        $conversation = Conversation::factory()->create(['number' => 123]);
        $threads = collect([Thread::factory()->create()]);
        
        $mailable = new UserNotification($user, $conversation, $threads, [], [], $mailbox);

        $this->assertInstanceOf(UserNotification::class, $mailable);
    }
}
