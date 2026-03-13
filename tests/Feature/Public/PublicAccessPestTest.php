<?php

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\Thread;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    Storage::fake('local');
});

test('public attachment download requires valid signature', function () {
    $mailbox = Mailbox::factory()->create();
    $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
    $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
    $attachment = Attachment::factory()->create([
        'thread_id' => $thread->id,
        'file_name' => 'test.txt',
        'file_dir' => 'attachments/test',
    ]);

    // Create actual file
    Storage::put($attachment->file_dir.'/'.$attachment->file_name, 'content');

    // Unsigned URL
    $url = route('attachments.public_download', ['id' => $attachment->id]);

    $this->get($url)->assertForbidden();
});

test('public attachment download succeeds with valid signature', function () {
    $mailbox = Mailbox::factory()->create();
    $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
    $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
    $attachment = Attachment::factory()->create([
        'thread_id' => $thread->id,
        'file_name' => 'test.txt',
        'file_dir' => 'attachments/test',
    ]);

    Storage::put($attachment->file_dir.'/'.$attachment->file_name, 'content');

    $url = URL::signedRoute('attachments.public_download', ['id' => $attachment->id]);

    $response = $this->get($url);

    $response->assertOk();
    $response->assertHeader('content-disposition', 'attachment; filename=test.txt');
});

test('public attachment download returns 404 for missing file', function () {
    $mailbox = Mailbox::factory()->create();
    $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
    $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
    $attachment = Attachment::factory()->create([
        'thread_id' => $thread->id,
        'file_name' => 'missing.txt',
        'file_dir' => 'attachments/test',
    ]);

    $url = URL::signedRoute('attachments.public_download', ['id' => $attachment->id]);

    $this->get($url)->assertNotFound();
});

test('public attachment download returns 404 for nonexistent attachment id', function () {
    $url = URL::signedRoute('attachments.public_download', ['id' => 999999]);
    $this->get($url)->assertNotFound();
});

test('tracking pixel works with valid signature', function () {
    $thread = Thread::factory()->create(['opened_at' => null]);

    $url = URL::signedRoute('track.pixel', ['id' => $thread->id]);

    $response = $this->get($url);

    $response->assertOk();
    $response->assertHeader('content-type', 'image/gif');

    expect($thread->fresh()->opened_at)->not->toBeNull();
});

test('tracking pixel fails with invalid signature', function () {
    $thread = Thread::factory()->create();

    $url = route('track.pixel', ['id' => $thread->id]); // Unsigned

    $this->get($url)->assertForbidden();
});
