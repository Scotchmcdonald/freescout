<?php

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\Thread;
use Illuminate\Support\Facades\URL;

test('tracking pixel updates thread opened_at timestamp', function () {
    $mailbox = Mailbox::factory()->create();
    $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
    $thread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'opened_at' => null,
    ]);

    $url = URL::signedRoute('track.pixel', ['id' => $thread->id]);

    $response = $this->get($url);

    $response->assertOk();
    $response->assertHeader('content-type', 'image/gif');

    expect($thread->fresh()->opened_at)->not->toBeNull();
});

test('tracking pixel requires valid signature', function () {
    $mailbox = Mailbox::factory()->create();
    $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
    $thread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'opened_at' => null,
    ]);

    // Unsigned - assuming the route requires signature?
    // CustomerExperienceTest used URL::signedRoute('track.pixel', ...)
    // Usually that implies it checks signature.

    $url = route('track.pixel', ['id' => $thread->id]);

    // If it's signed middleware protected, it should be 403.
    // I'll assume 403 for now, standard for signed routes.
    $this->get($url)->assertForbidden();
});
