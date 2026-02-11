<?php

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;

test('user can view conversations list', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX, 'name' => 'Inbox']);
    
    $conv1 = Conversation::factory()->for($mailbox)->create([
        'subject' => 'First Support Request',
        'state' => Conversation::STATE_PUBLISHED,
    ]);
    $conv2 = Conversation::factory()->for($mailbox)->create([
        'subject' => 'Second Support Request', 
        'state' => Conversation::STATE_PUBLISHED,
    ]);

    $this->actingAs($user)->get(route('conversations.index', $mailbox))
        ->assertOk()
        ->assertViewIs('conversations.index')
        ->assertSee($mailbox->name)
        ->assertSee('First Support Request')
        ->assertSee('Second Support Request')
        ->assertViewHas('conversations');
});

test('user can create conversation', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    
    $customer = Customer::factory()->create();
    $email = Email::factory()->create(['customer_id' => $customer->id, 'type' => 1]);

    $this->actingAs($user)->post(route('conversations.store', $mailbox), [
        'customer_id' => $customer->id,
        'subject' => 'Test Conversation',
        'body' => 'This is a test message',
        'to' => [$email->email],
    ])->assertRedirect();

    $this->assertDatabaseHas('conversations', [
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'subject' => 'Test Conversation',
    ]);
});

test('user can view conversation', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    
    $conversation = Conversation::factory()->for($mailbox)->create(['subject' => 'Important Issue']);
    
    Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'body' => 'Initial customer message',
        'state' => 2,
    ]);
    Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'body' => 'Agent response',
        'state' => 2,
    ]);

    $this->actingAs($user)->get(route('conversations.show', $conversation))
        ->assertOk()
        ->assertViewIs('conversations.show')
        ->assertSee('Important Issue')
        ->assertSee('Initial customer message')
        ->assertSee('Agent response');
});

test('user can reply to conversation', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);

    $conversation = Conversation::factory()->for($mailbox)->create();
    $customerEmail = Email::factory()->create(['customer_id' => $conversation->customer_id]);

    $this->actingAs($user)->post(route('conversations.reply', $conversation), [
        'body' => 'This is a reply',
        'to' => [$customerEmail->email],
    ])->assertRedirect();

    $this->assertDatabaseHas('threads', [
        'conversation_id' => $conversation->id,
        'body' => '<p>This is a reply</p>',
        'created_by_user_id' => $user->id,
    ]);
});

test('user can update conversation status', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);

    $conversation = Conversation::factory()->for($mailbox)->create(['status' => Conversation::STATUS_ACTIVE]);

    $this->actingAs($user)->patch(route('conversations.update', $conversation), [
        'status' => Conversation::STATUS_CLOSED,
    ])->assertRedirect();

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'status' => Conversation::STATUS_CLOSED,
    ]);
});

test('user can assign conversation', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);

    $conversation = Conversation::factory()->for($mailbox)->create();
    $assignee = User::factory()->create();
    $mailbox->users()->attach($assignee);

    $this->actingAs($user)->patch(route('conversations.update', $conversation), [
        'user_id' => $assignee->id,
    ])->assertRedirect();

    expect($conversation->refresh()->user_id)->toBe($assignee->id);
});

test('closed conversation shows correct badge', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);

    $conversation = Conversation::factory()
        ->for($mailbox)
        ->create(['status' => Conversation::STATUS_CLOSED]);

    $this->actingAs($user)->get(route('conversations.show', $conversation))
        ->assertOk()
        ->assertSee('Closed');
});
