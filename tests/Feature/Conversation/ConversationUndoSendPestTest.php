<?php

declare(strict_types=1);

namespace Tests\Feature\Conversation;

use App\Jobs\SendConversationReplyJob;
use App\Mail\CustomerReply;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->mailbox = Mailbox::factory()->create();
    $this->user->mailboxes()->attach($this->mailbox);
    $this->customer = Customer::factory()->create();
    $this->conversation = Conversation::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'customer_id' => $this->customer->id,
        'customer_email' => $this->customer->getMainEmail(),
    ]);
});

test('reply dispatches job with delay', function () {
    Queue::fake();

    $response = $this->actingAs($this->user)->post(route('conversations.reply', $this->conversation), [
        'body' => 'This is a reply',
        'type' => 1, // Reply
    ]);

    $response->assertRedirect();

    Queue::assertPushed(SendConversationReplyJob::class, function ($job) {
        return ! is_null($job->delay);
    });
});

test('undo send changes thread to draft', function () {
    // Removed Queue::fake() to isolate issues

    // Create a thread that was just "sent"
    $thread = Thread::factory()->create([
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'type' => Thread::TYPE_MESSAGE,
        'state' => Thread::STATE_PUBLISHED,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->post(route('conversations.undo_send', ['conversation' => $this->conversation, 'thread' => $thread]));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $thread->refresh();

    expect($thread->state)->toBe(Thread::STATE_DRAFT);
    expect($thread->type)->toBe(Thread::TYPE_DRAFT);
});

test('undo send fails after timeout', function () {
    $thread = Thread::factory()->create([
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'type' => Thread::TYPE_MESSAGE,
        'state' => Thread::STATE_PUBLISHED,
        'created_at' => now()->subSeconds(60), // Safe margin
    ]);

    $response = $this->actingAs($this->user)->post(route('conversations.undo_send', ['conversation' => $this->conversation, 'thread' => $thread]));

    $response->assertRedirect();
    $response->assertSessionHasErrors('error');

    $thread->refresh();
    expect($thread->state)->toBe(Thread::STATE_PUBLISHED);
});

test('job does not send email if thread is draft', function () {
    Mail::fake();

    $thread = Thread::factory()->create([
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'type' => Thread::TYPE_DRAFT, // It was undone
        'state' => Thread::STATE_DRAFT,
    ]);

    $job = new SendConversationReplyJob($this->conversation, $thread);
    $job->handle();

    Mail::assertNothingSent();
});

test('job sends email if thread is published', function () {
    Mail::fake();

    $thread = Thread::factory()->create([
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'type' => Thread::TYPE_MESSAGE,
        'state' => Thread::STATE_PUBLISHED,
    ]);

    $job = new SendConversationReplyJob($this->conversation, $thread);
    $job->handle();

    Mail::assertSent(CustomerReply::class);
});
