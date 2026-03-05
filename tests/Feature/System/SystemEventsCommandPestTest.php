<?php

use App\Events\CustomerCreatedConversation;
use App\Events\CustomerReplied;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('command fires events with existing conversation', function () {
    Event::fake([
        CustomerCreatedConversation::class,
        CustomerReplied::class,
    ]);

    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();
    $conversation = Conversation::factory()
        ->for($mailbox)
        ->for($customer)
        ->create();
    $thread = Thread::factory()
        ->for($conversation)
        ->create([
            'customer_id' => $customer->id,
            'created_by_customer_id' => $customer->id,
        ]);

    $this->artisan('freescout:test-events')
        ->expectsOutput('Testing event system...')
        ->expectsOutputToContain("Testing with Conversation ID: {$conversation->id}")
        ->expectsOutputToContain("Customer: {$customer->getMainEmail()}")
        ->expectsOutput('Firing CustomerCreatedConversation event...')
        ->expectsOutput('Firing CustomerReplied event...')
        ->expectsOutputToContain('Events dispatched. Check storage/logs/laravel.log')
        ->assertExitCode(0);

    Event::assertDispatched(CustomerCreatedConversation::class, function ($event) use ($conversation, $thread, $customer) {
        return $event->conversation->id === $conversation->id
            && $event->thread->id === $thread->id;
    });

    Event::assertDispatched(CustomerReplied::class, function ($event) use ($conversation, $thread, $customer) {
        return $event->conversation->id === $conversation->id
            && $event->thread->id === $thread->id;
    });
});

test('command fails when no conversations exist', function () {
    $this->artisan('freescout:test-events')
        ->expectsOutput('No conversations found. Run freescout:fetch-emails first.')
        ->assertExitCode(1);
});

test('command fails when conversation has no threads', function () {
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();
    Conversation::factory()
        ->for($mailbox)
        ->for($customer)
        ->create();

    $this->artisan('freescout:test-events')
        ->expectsOutput('Conversation missing thread or customer.')
        ->assertExitCode(1);
});

test('command fails when conversation has no customer', function () {
    $mailbox = Mailbox::factory()->create();
    Conversation::factory()
        ->for($mailbox)
        ->create(['customer_id' => null]);

    $this->artisan('freescout:test-events')
        ->expectsOutput('Conversation missing thread or customer.')
        ->assertExitCode(1);
});

test('command dispatches both events correctly', function () {
    Event::fake();

    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();
    $conversation = Conversation::factory()
        ->for($mailbox)
        ->for($customer)
        ->create();
    Thread::factory()->for($conversation)->create();

    $this->artisan('freescout:test-events')->assertExitCode(0);

    Event::assertDispatchedTimes(CustomerCreatedConversation::class, 1);
    Event::assertDispatchedTimes(CustomerReplied::class, 1);
});

test('command uses first conversation with threads', function () {
    Event::fake();

    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();

    $conversation1 = Conversation::factory()
        ->for($mailbox)
        ->for($customer)
        ->create();
    Thread::factory()->for($conversation1)->create();

    $conversation2 = Conversation::factory()
        ->for($mailbox)
        ->for($customer)
        ->create();
    Thread::factory()->for($conversation2)->create();

    $this->artisan('freescout:test-events')
        ->expectsOutputToContain("Testing with Conversation ID: {$conversation1->id}")
        ->assertExitCode(0);
});
