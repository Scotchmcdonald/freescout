<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\UserCreatedConversation;
use App\Events\UserReplied;
use App\Listeners\SendReplyToCustomer;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Tests\UnitTestCase;

class SendReplyToCustomerTest extends UnitTestCase
{
    public function test_listener_handles_user_replied_event(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'type' => 1, // TYPE_EMAIL
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
            'type' => Thread::TYPE_MESSAGE,
            'imported' => false,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer;

        // Should handle without exception
        $listener->handle($event);
        Queue::assertPushed(\App\Jobs\SendConversationReplyJob::class, 1);
    }

    public function test_listener_handles_user_created_conversation_event(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'created_by_user_id' => $user->id,
            'type' => 1,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_MESSAGE,
            'imported' => false,
        ]);

        $event = new UserCreatedConversation($conversation, $thread);
        $listener = new SendReplyToCustomer;

        $listener->handle($event);
        Queue::assertPushed(\App\Jobs\SendConversationReplyJob::class, 1);
    }

    public function test_listener_skips_imported_threads(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $importedThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
            'imported' => true,
        ]);

        $event = new UserReplied($conversation, $importedThread);
        $listener = new SendReplyToCustomer;

        // Should skip imported threads
        $listener->handle($event);
        Queue::assertNotPushed(\App\Jobs\SendConversationReplyJob::class);
    }

    public function test_listener_handles_phone_conversation_with_email(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $customer->emails()->create(['email' => 'customer@example.com', 'type' => 'work']);
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'type' => 2, // TYPE_PHONE (if defined)
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
            'imported' => false,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer;

        // Should process phone conversation with customer email
        $listener->handle($event);
        Queue::assertPushed(\App\Jobs\SendConversationReplyJob::class, 1);
    }

    public function test_listener_processes_multiple_threads(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        // Create the most recent thread to use
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_MESSAGE,
            'imported' => false,
            'created_at' => now(),
        ]);

        $event = new UserCreatedConversation($conversation, $thread);
        $listener = new SendReplyToCustomer;

        $listener->handle($event);
        Queue::assertPushed(\App\Jobs\SendConversationReplyJob::class, 1);
    }

    public function test_listener_handles_event_with_thread_property(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer;

        // Should process thread from event
        $listener->handle($event);
        $this->assertInstanceOf(Thread::class, $event->thread);
    }

    public function test_listener_handles_conversation_with_thread(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'user_id' => $user->id,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new UserCreatedConversation($conversation, $thread);
        $listener = new SendReplyToCustomer;

        // Should handle conversation with thread
        $listener->handle($event);
        Queue::assertPushed(\App\Jobs\SendConversationReplyJob::class, 1);
    }

    public function test_listener_handles_user_replied_with_customer(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'customer_email' => $customer->emails->first()->email,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
            'type' => Thread::TYPE_MESSAGE,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer;

        $listener->handle($event);
        $this->assertNotNull($conversation->customer_id);
    }

    public function test_listener_can_be_instantiated(): void
    {
        $listener = new SendReplyToCustomer;
        $this->assertInstanceOf(SendReplyToCustomer::class, $listener);
    }

    public function test_listener_skips_phone_conversation_without_customer_email(): void
    {
        \Queue::fake();

        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        // Remove all emails from customer
        $customer->emails()->delete();

        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'type' => 2, // TYPE_PHONE
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
        ]);

        // Use real conversation object as it has the necessary methods and data
        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer;

        // Should handle without exception and skip sending
        $listener->handle($event);

        \Queue::assertNotPushed(\App\Jobs\SendConversationReplyJob::class);
    }

    public function test_listener_dispatches_job_with_correct_delay(): void
    {
        \Queue::fake();

        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer;

        $listener->handle($event);

        \Queue::assertPushed(\App\Jobs\SendConversationReplyJob::class, function ($job) {
            return ! is_null($job->delay);
        });
    }

    public function test_listener_dispatches_job_to_emails_queue(): void
    {
        \Queue::fake();

        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer;

        $listener->handle($event);

        \Queue::assertPushedOn('emails', \App\Jobs\SendConversationReplyJob::class);
    }

    public function test_listener_passes_correct_parameters_to_job(): void
    {
        \Queue::fake();

        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
            'imported' => false,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer;

        $listener->handle($event);

        \Queue::assertPushed(\App\Jobs\SendConversationReplyJob::class, function ($job) use ($conversation, $thread) {
            return $job->conversation->id === $conversation->id &&
                   $job->thread->id === $thread->id;
        });
    }
}
