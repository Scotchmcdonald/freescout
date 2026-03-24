<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\Thread;

test('conversation increments thread count', function () {
    $mailbox = Mailbox::factory()->create();
    $conversation = Conversation::factory()->for($mailbox)->create(['threads_count' => 0]);

    Thread::factory()->create([
        'conversation_id' => $conversation->id,
    ]);

    $conversation->refresh();

    // Check if logic exists. Often this is handled by Model Events/Observers.
    // Legacy test expected it to work.
    expect($conversation->threads_count)->toBe(1);
});
