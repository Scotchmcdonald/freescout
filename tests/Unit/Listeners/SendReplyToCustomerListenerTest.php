<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\UserCreatedConversation;
use App\Events\UserReplied;
use App\Jobs\SendConversationReply;
use App\Listeners\SendReplyToCustomer;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendReplyToCustomerListenerTest extends TestCase
{
    use RefreshDatabase;

    protected Mailbox $mailbox;
    protected User $user;
    protected Customer $customer;
    protected Conversation $conversation;
    protected Thread $thread;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailbox = Mailbox::factory()->create();
        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create();
        
        Email::factory()->create([
            'customer_id' => $this->customer->id,
            'email' => 'customer@example.com',
        ]);

        $this->conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create([
                'customer_id' => $this->customer->id,
                'type' => Conversation::TYPE_EMAIL,
            ]);

        $this->thread = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
            'created_by_user_id' => $this->user->id,
            'type' => Thread::TYPE_MESSAGE,
            'imported' => false,
        ]);
    }

    public function test_dispatches_send_reply_job_for_user_replied(): void
    {
        Queue::fake();

        $listener = new SendReplyToCustomer();
        $event = new UserReplied($this->conversation, $this->thread);

        $listener->handle($event);

        Queue::assertPushed(SendConversationReply::class, function ($job) {
            return $job->conversation->id === $this->conversation->id;
        });
    }

    public function test_dispatches_send_reply_job_for_user_created_conversation(): void
    {
        Queue::fake();

        $listener = new SendReplyToCustomer();
        $event = new UserCreatedConversation($this->conversation, $this->thread);

        $listener->handle($event);

        Queue::assertPushed(SendConversationReply::class);
    }

    public function test_skips_imported_threads(): void
    {
        Queue::fake();

        $this->thread->update(['imported' => true]);

        $listener = new SendReplyToCustomer();
        $event = new UserReplied($this->conversation, $this->thread);

        $listener->handle($event);

        Queue::assertNotPushed(SendConversationReply::class);
    }

    public function test_skips_phone_conversation_without_customer_email(): void
    {
        Queue::fake();

        // Create customer without email
        $customerNoEmail = Customer::factory()->withoutEmail()->create();
        
        $phoneConversation = Conversation::factory()
            ->for($this->mailbox)
            ->create([
                'customer_id' => $customerNoEmail->id,
                'type' => Conversation::TYPE_PHONE,
            ]);

        $thread = Thread::factory()->create([
            'conversation_id' => $phoneConversation->id,
            'created_by_user_id' => $this->user->id,
            'imported' => false,
        ]);

        $listener = new SendReplyToCustomer();
        $event = new UserReplied($phoneConversation, $thread);

        $listener->handle($event);

        Queue::assertNotPushed(SendConversationReply::class);
    }

    public function test_handles_phone_conversation_with_customer_email(): void
    {
        Queue::fake();

        $phoneConversation = Conversation::factory()
            ->for($this->mailbox)
            ->create([
                'customer_id' => $this->customer->id,
                'type' => Conversation::TYPE_PHONE,
            ]);

        $thread = Thread::factory()->create([
            'conversation_id' => $phoneConversation->id,
            'created_by_user_id' => $this->user->id,
            'imported' => false,
        ]);

        $listener = new SendReplyToCustomer();
        $event = new UserReplied($phoneConversation, $thread);

        $listener->handle($event);

        Queue::assertPushed(SendConversationReply::class);
    }

    public function test_skips_conversation_without_customer(): void
    {
        Queue::fake();

        $conversationNoCustomer = Conversation::factory()
            ->for($this->mailbox)
            ->create([
                'customer_id' => null,
            ]);

        $thread = Thread::factory()->create([
            'conversation_id' => $conversationNoCustomer->id,
            'created_by_user_id' => $this->user->id,
            'imported' => false,
        ]);

        $listener = new SendReplyToCustomer();
        $event = new UserReplied($conversationNoCustomer, $thread);

        $listener->handle($event);

        Queue::assertNotPushed(SendConversationReply::class);
    }

    public function test_skips_chat_conversation(): void
    {
        Queue::fake();

        $chatConversation = Conversation::factory()
            ->for($this->mailbox)
            ->create([
                'customer_id' => $this->customer->id,
                'type' => Conversation::TYPE_CHAT,
            ]);

        $thread = Thread::factory()->create([
            'conversation_id' => $chatConversation->id,
            'created_by_user_id' => $this->user->id,
            'imported' => false,
        ]);

        $listener = new SendReplyToCustomer();
        $event = new UserReplied($chatConversation, $thread);

        $listener->handle($event);

        Queue::assertNotPushed(SendConversationReply::class);
    }
}
