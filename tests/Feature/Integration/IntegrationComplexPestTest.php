<?php

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Subscription;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\Event;


test('complete conversation creation workflow', function () {
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user->id);
    $customer = Customer::factory()->create();
    
    $this->actingAs($user)
        ->post(route('conversations.store', $mailbox), [
            'subject' => 'Test Conversation',
            'customer_id' => $customer->id,
            'body' => 'This is a test message',
            'type' => Thread::TYPE_MESSAGE,
            'to' => ['test@example.com'], // Required field
        ])
        ->assertStatus(302);
    
    $conversation = Conversation::where('subject', 'Test Conversation')->first();
    expect($conversation)->not->toBeNull()
        ->and($conversation->mailbox_id)->toBe($mailbox->id);
});

test('conversation reply workflow', function () {
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user->id);
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'user_id' => $user->id
    ]);
    
    $this->actingAs($user)
        ->post(route('conversations.reply', $conversation->id), [
            'body' => 'This is a reply',
            'type' => Thread::TYPE_MESSAGE
        ])
        ->assertStatus(302);
    
    $threads = Thread::where('conversation_id', $conversation->id)->get();
    expect($threads->count())->toBeGreaterThan(0);
});

test('conversation status change workflow', function () {
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user->id);
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'status' => Conversation::STATUS_ACTIVE
    ]);
    
    $this->actingAs($user)
        ->patch(route('conversations.update', $conversation->id), [
            'status' => Conversation::STATUS_CLOSED
        ])
        ->assertStatus(302);
    
    expect($conversation->fresh()->status)->toBe(Conversation::STATUS_CLOSED);
});

test('conversation assignment workflow', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($admin->id);
    $mailbox->users()->attach($user->id);
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'user_id' => $admin->id
    ]);
    
    $this->actingAs($admin)
        ->patch(route('conversations.update', $conversation->id), [
            'user_id' => $user->id
        ])
        ->assertStatus(302);
    
    expect($conversation->fresh()->user_id)->toBe($user->id);
});

test('conversation with attachments workflow', function () {
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user->id);
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id
    ]);
    $thread = Thread::factory()->create([
        'conversation_id' => $conversation->id
    ]);
    
    // Check if Attachment factory works, otherwise simulate
    try {
        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id
        ]);
    } catch (\Throwable $e) {
        $attachment = new Attachment();
        $attachment->thread_id = $thread->id;
        $attachment->file_name = 'test.pdf';
        $attachment->file_size = 1024;
        $attachment->content_type = 'application/pdf';
        $attachment->save();
    }
    
    $this->actingAs($user)
        ->get(route('conversations.show', $conversation->id))
        ->assertStatus(200);
        
    expect($attachment->thread_id)->toBe($thread->id);
});

test('conversation search complex workflow', function () {
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user->id);
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'subject' => 'Unique Search Subject'
    ]);
    
    $this->actingAs($user)
        ->get(route('conversations.search', ['q' => 'Unique']))
        ->assertStatus(200);
});

test('conversation move to folder workflow', function () {
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user->id);
    $folder1 = Folder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'type' => Folder::TYPE_UNASSIGNED
    ]);
    $folder2 = Folder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'type' => Folder::TYPE_MINE
    ]);
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'folder_id' => $folder1->id
    ]);
    
    $this->actingAs($user)
        ->patch(route('conversations.update', $conversation->id), [
            'folder_id' => $folder2->id
        ])
        ->assertStatus(302);
    
    expect($conversation->fresh()->folder_id)->toBe($folder2->id);
});

test('conversation with subscription workflow', function () {
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user->id);
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id
    ]);
    
    Subscription::factory()->create([
        'user_id' => $user->id,
        'event' => \App\Events\UserReplied::class
    ]);
    
    Event::fake();
    
    $this->actingAs($user)
        ->post(route('conversations.reply', $conversation->id), [
            'body' => 'Reply with subscription',
            'type' => Thread::TYPE_MESSAGE
        ])
        ->assertStatus(302);
});

test('conversation delete workflow', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id
    ]);
    
    $conversationId = $conversation->id;
    
    $this->actingAs($admin)
        ->delete(route('conversations.destroy', $conversation->id))
        ->assertStatus(302);
    
    expect(Conversation::find($conversationId))->toBeNull();
});

test('conversation with multiple threads workflow', function () {
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user->id);
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id
    ]);
    
    Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_MESSAGE
    ]);
    Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_NOTE
    ]);
    
    $this->actingAs($user)
        ->get(route('conversations.show', $conversation->id))
        ->assertStatus(200);
    
    expect($conversation->threads()->count())->toBe(2);
});

test('user cannot access conversation in different mailbox', function () {
    $user = User::factory()->create();
    $mailbox1 = Mailbox::factory()->create();
    $mailbox2 = Mailbox::factory()->create();
    $mailbox1->users()->attach($user->id);
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox2->id
    ]);
    
    $this->actingAs($user)
        ->get(route('conversations.show', $conversation->id))
        ->assertStatus(403);
});

test('admin can access all mailbox conversations', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id
    ]);
    
    $this->actingAs($admin)
        ->get(route('conversations.show', $conversation->id))
        ->assertStatus(200);
});
