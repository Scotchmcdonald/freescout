<?php

namespace Tests\Feature;

use App\Jobs\SendConversationReply;
use App\Mail\CustomerReply;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UndoSendTest extends TestCase
{
    use RefreshDatabase;

    public function test_reply_dispatches_job_with_delay()
    {
        Queue::fake();

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => $customer->getMainEmail(),
        ]);

        $response = $this->actingAs($user)->post(route('conversations.reply', $conversation), [
            'body' => 'This is a reply',
            'type' => 1, // Reply
        ]);

        $response->assertRedirect();
        
        Queue::assertPushed(SendConversationReply::class, function ($job) {
            return !is_null($job->delay);
        });
    }

    public function test_undo_send_changes_thread_to_draft()
    {
        Queue::fake();

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => $customer->getMainEmail(),
        ]);

        // Create a thread that was just "sent"
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => Thread::TYPE_MESSAGE,
            'state' => Thread::STATE_PUBLISHED,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('conversations.undo_send', ['conversation' => $conversation, 'thread' => $thread]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $thread->refresh();
        $this->assertEquals(Thread::STATE_DRAFT, $thread->state);
        $this->assertEquals(Thread::TYPE_DRAFT, $thread->type);
    }

    public function test_undo_send_fails_after_timeout()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => $customer->getMainEmail(),
        ]);

        // Create a thread that was sent 20 seconds ago
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => Thread::TYPE_MESSAGE,
            'state' => Thread::STATE_PUBLISHED,
            'created_at' => now()->subSeconds(20),
        ]);

        $response = $this->actingAs($user)->post(route('conversations.undo_send', ['conversation' => $conversation, 'thread' => $thread]));

        $response->assertSessionHasErrors(['error']);
        
        $thread->refresh();
        $this->assertEquals(Thread::STATE_PUBLISHED, $thread->state);
    }

    public function test_job_does_not_send_email_if_thread_is_draft()
    {
        Mail::fake();

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => $customer->getMainEmail(),
        ]);

        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => Thread::TYPE_DRAFT, // It was undone
            'state' => Thread::STATE_DRAFT,
        ]);

        $job = new SendConversationReply($conversation, $thread);
        $job->handle();

        Mail::assertNothingSent();
    }

    public function test_job_sends_email_if_thread_is_published()
    {
        Mail::fake();

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => $customer->getMainEmail(),
        ]);

        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => Thread::TYPE_MESSAGE,
            'state' => Thread::STATE_PUBLISHED,
        ]);

        $job = new SendConversationReply($conversation, $thread);
        $job->handle();

        Mail::assertSent(CustomerReply::class);
    }
}
