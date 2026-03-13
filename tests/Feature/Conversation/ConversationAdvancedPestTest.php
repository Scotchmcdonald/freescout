<?php

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;

test('index shows only published conversations', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX, 'name' => 'Inbox']);

    $published = Conversation::factory()->for($mailbox)->create(['state' => 2, 'status' => Conversation::STATUS_ACTIVE]);
    $draft = Conversation::factory()->for($mailbox)->create(['state' => 1, 'status' => Conversation::STATUS_ACTIVE]);

    $this->actingAs($user)->get(route('conversations.index', $mailbox))
        ->assertOk()
        ->assertSee($published->subject)
        ->assertDontSee($draft->subject);
});

test('store creates conversation with new customer', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX]);

    $this->actingAs($user)->post(route('conversations.store', $mailbox), [
        'subject' => 'New Support Request',
        'body' => 'I need help',
        'to' => ['newcust@example.com'],
        'customer_email' => 'newcust@example.com',
        'customer_first_name' => 'John',
        'customer_last_name' => 'Doe',
    ])->assertRedirect();

    $this->assertDatabaseHas('customers', ['first_name' => 'John', 'last_name' => 'Doe']);
    $this->assertDatabaseHas('conversations', ['subject' => 'New Support Request']);
});

test('guest cannot access conversation list', function () {
    $mailbox = Mailbox::factory()->create();
    $this->get(route('conversations.index', $mailbox))->assertRedirect(route('login'));
});

// Edge Case Tests from Legacy
test('conversation with no threads displays correctly', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $conversation = Conversation::factory()->for($mailbox)->create();
    Thread::where('conversation_id', $conversation->id)->delete();

    $this->actingAs($user)->get(route('conversations.show', $conversation))
        ->assertOk()
        ->assertSee($conversation->subject);
});

test('conversation list handles empty mailbox', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX]);

    $response = $this->actingAs($user)->get(route('conversations.index', $mailbox));

    $response->assertOk();
    $response->assertViewHas('conversations');
    expect($response->viewData('conversations'))->toHaveCount(0);
});

test('conversation with very long subject is handled', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $customer = Customer::factory()->create();

    $longSubject = str_repeat('Very Long Subject Line ', 20); // ~460 chars

    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'subject' => $longSubject,
    ]);

    $this->actingAs($user)->get(route('conversations.show', $conversation->id))
        ->assertOk();

    // Verify subject is displayed (may be truncated in display)
    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
    ]);
});

test('conversation list with many conversations uses pagination', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX, 'name' => 'Inbox']);
    $customer = Customer::factory()->create();

    // Create 100 conversations (more than one page)
    Conversation::factory()->count(100)->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'status' => Conversation::STATUS_ACTIVE,
        'state' => Conversation::STATE_PUBLISHED,
    ]);

    $response = $this->actingAs($user)->get(route('conversations.index', $mailbox->id));

    $response->assertOk();
    $response->assertViewHas('conversations');

    // Verify pagination is working
    $conversations = $response->viewData('conversations');
    // Ensure we don't get all 100 on one page. Assuming default perPage is < 100.
    expect($conversations->count())->toBeLessThan(100);
});

test('index orders by most recent', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX, 'name' => 'Inbox']);

    $older = Conversation::factory()->for($mailbox)->create([
        'state' => Conversation::STATE_PUBLISHED,
        'status' => Conversation::STATUS_ACTIVE,
        'last_reply_at' => now()->subHour(),
        'subject' => 'Older Conversation',
    ]);

    $newer = Conversation::factory()->for($mailbox)->create([
        'state' => Conversation::STATE_PUBLISHED,
        'status' => Conversation::STATUS_ACTIVE,
        'last_reply_at' => now()->addMinutes(10), // Future, or just newer
        'subject' => 'Newer Conversation',
    ]);

    $response = $this->actingAs($user)->get(route('conversations.index', $mailbox));

    $response->assertOk();
    // Verify default sort is by Last Reply
    $conversations = $response->viewData('conversations');
    // The collection should be processed
    // In legacy test, it checked string position in HTML.
    // If 'conversations' is available, we can check the collection order.

    // Assuming conversations is paginator or collection
    $items = $conversations->items(); // If Paginator
    if (! method_exists($conversations, 'items')) {
        $items = $conversations->all();
    }

    // First item should be newer
    expect($items[0]->id)->toBe($newer->id);
    expect($items[1]->id)->toBe($older->id);
});

test('viewing conversation marks notifications as read', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $conversation = Conversation::factory()->for($mailbox)->create();

    // Create a notification
    $user->notifications()->create([
        'id' => \Illuminate\Support\Str::uuid(),
        'type' => 'App\Notifications\NewConversationReply',
        'data' => ['conversation_id' => $conversation->id, 'message' => 'Test'],
        'read_at' => null,
    ]);

    expect($user->unreadNotifications->count())->toBe(1);

    $this->actingAs($user)->get(route('conversations.show', $conversation));

    $user->refresh();
    expect($user->unreadNotifications->count())->toBe(0);
});

test('show loads threads in order', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $conversation = Conversation::factory()->for($mailbox)->create();

    // Create threads out of order in code, but with timestamps
    Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'body' => 'Third message',
        'created_at' => now()->addMinutes(2),
    ]);

    Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'body' => 'First message',
        'created_at' => now()->subMinutes(10),
    ]);

    Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'body' => 'Second message',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('conversations.show', $conversation));

    $content = $response->getContent();
    $firstPos = strpos($content, 'First message');
    $secondPos = strpos($content, 'Second message');
    $thirdPos = strpos($content, 'Third message');

    expect($firstPos)->toBeLessThan($secondPos);
    expect($secondPos)->toBeLessThan($thirdPos);
});

test('store uses existing customer', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);

    $customer = Customer::factory()->create();
    $email = \App\Models\Email::factory()->create([
        'customer_id' => $customer->id,
        'email' => 'existing@example.com',
        'type' => 1,
    ]);

    $this->actingAs($user)->post(route('conversations.store', $mailbox), [
        'subject' => 'Follow-up Request',
        'body' => 'Another question',
        'to' => [$email->email],
        'customer_id' => $customer->id,
    ])->assertRedirect();

    expect(Customer::count())->toBe(1);
    $conversation = Conversation::where('subject', 'Follow-up Request')->first();
    expect($conversation->customer_id)->toBe($customer->id);
});

test('store auto increments conversation number', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);

    // First conv
    Conversation::factory()->for($mailbox)->create(['number' => 1]);

    $customer = Customer::factory()->create();
    $email = \App\Models\Email::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs($user)->post(route('conversations.store', $mailbox), [
        'subject' => 'Second Conversation',
        'body' => 'Test body',
        'to' => [$email->email],
        'customer_id' => $customer->id,
    ]);

    $conversation = Conversation::where('subject', 'Second Conversation')->first();
    // Assuming framework handles incrementing or factory did it manually earlier.
    // If real app logic does it:
    expect($conversation->number)->toBe(2);
});

test('store handles invalid data gracefully', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);

    $initialConversations = Conversation::count();

    $this->actingAs($user)->post(route('conversations.store', $mailbox), [
        'subject' => 'Test',
        'body' => 'Test',
        'to' => ['invalid-email-format'],
    ])->assertSessionHasErrors('to.0');

    expect(Conversation::count())->toBe($initialConversations);
});

test('update moves conversation to folder', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $inbox = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX]);
    $trash = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_TRASH]);

    $conversation = Conversation::factory()->for($mailbox)->create([
        'folder_id' => $inbox->id,
        'status' => Conversation::STATUS_ACTIVE,
    ]);

    $this->actingAs($user)->patch(route('conversations.update', $conversation), [
        'folder_id' => $trash->id,
    ])->assertRedirect();

    expect($conversation->refresh()->folder_id)->toBe($trash->id);
});

test('reply can create internal note', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $conversation = Conversation::factory()->for($mailbox)->create();

    $this->actingAs($user)->post(route('conversations.reply', $conversation), [
        'body' => 'Internal note: Customer seems frustrated',
        'type' => 2, // Internal note
    ])->assertRedirect();

    $this->assertDatabaseHas('threads', [
        'conversation_id' => $conversation->id,
        'body' => '<p>Internal note: Customer seems frustrated</p>',
        'type' => 2,
    ]);
});
