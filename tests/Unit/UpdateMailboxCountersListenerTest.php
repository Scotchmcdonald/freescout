<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Events\ConversationStatusChanged;
use App\Listeners\UpdateMailboxCounters;
use App\Models\Conversation;
use App\Models\Mailbox;
use Tests\UnitTestCase;

class UpdateMailboxCountersListenerTest extends UnitTestCase
{
    public function test_listener_has_handle_method(): void
    {
        $listener = new UpdateMailboxCounters;
        $this->assertTrue(method_exists($listener, 'handle'));
    }

    public function test_listener_handles_status_changed_event(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $event = new ConversationStatusChanged($conversation);
        $listener = new UpdateMailboxCounters;

        // Should not throw an exception
        $listener->handle($event);
        $this->assertTrue(true);
    }
}
