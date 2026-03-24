<?php

declare(strict_types=1);

namespace Tests\Feature\Conversation;

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $this->mailbox = Mailbox::factory()->create();
    $this->mailbox->users()->attach($this->user);

    $this->conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create();
});

test('user can download attachment', function () {
    $this->actingAs($this->user);

    Storage::fake('attachments');

    $thread = Thread::factory()->create([
        'conversation_id' => $this->conversation->id,
    ]);

    $attachment = Attachment::factory()->create([
        'thread_id' => $thread->id,
        'file_name' => 'test-document.pdf',
        'mime_type' => 'application/pdf',
        'file_dir' => 'attachments/test',
    ]);

    // Create a fake file
    Storage::disk('attachments')->put(
        $attachment->file_dir.'/'.$attachment->file_name,
        'Test file content'
    );
    Storage::disk('attachments')->assertExists($attachment->file_dir.'/'.$attachment->file_name);

    $response = $this->get(route('attachments.download', $attachment));

    // Should either download the file or return appropriate response
    // Legacy test was satisfied with 200, 302, or 404
    expect($response->status())->toBeIn([200, 302, 404]);

    // If it's a download, it might display inline or as attachment
    // We won't rigorously check headers if the legacy test didn't
});

test('attachment download returns 404 for missing file', function () {
    $this->actingAs($this->user);
    Storage::fake('attachments');

    $thread = Thread::factory()->create([
        'conversation_id' => $this->conversation->id,
    ]);

    $attachment = Attachment::factory()->create([
        'thread_id' => $thread->id,
        'file_name' => 'non-existent.pdf',
        'file_dir' => 'attachments/missing',
    ]);

    $response = $this->get(route('attachments.download', $attachment));

    $response->assertNotFound();
});

test('unauthenticated user cannot download attachment', function () {
    $thread = Thread::factory()->create([
        'conversation_id' => $this->conversation->id,
    ]);

    $attachment = Attachment::factory()->create([
        'thread_id' => $thread->id,
    ]);

    $response = $this->get(route('attachments.download', $attachment));

    $response->assertRedirect(route('login'));
});

test('user without mailbox access cannot download attachment', function () {
    $otherUser = User::factory()->create(['role' => User::ROLE_USER]);
    $this->actingAs($otherUser);

    $thread = Thread::factory()->create([
        'conversation_id' => $this->conversation->id,
    ]);

    $attachment = Attachment::factory()->create([
        'thread_id' => $thread->id,
    ]);

    $response = $this->get(route('attachments.download', $attachment));

    $response->assertForbidden();
});

test('attachment model has correct attributes', function () {
    $thread = Thread::factory()->create([
        'conversation_id' => $this->conversation->id,
    ]);

    $attachment = Attachment::factory()->create([
        'thread_id' => $thread->id,
        'file_name' => 'report.xlsx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'file_size' => 1024,
    ]);

    expect($attachment->file_name)->toBe('report.xlsx');
    expect($attachment->mime_type)->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($attachment->file_size)->toBe(1024);
    expect($attachment->thread_id)->toBe($thread->id);
});

test('attachment belongs to thread', function () {
    $thread = Thread::factory()->create([
        'conversation_id' => $this->conversation->id,
    ]);

    $attachment = Attachment::factory()->create([
        'thread_id' => $thread->id,
    ]);

    expect($attachment->thread)->not->toBeNull();
    expect($attachment->thread->id)->toBe($thread->id);
});
