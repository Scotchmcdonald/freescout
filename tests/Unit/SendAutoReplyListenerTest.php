<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Events\CustomerCreatedConversation;
use App\Jobs\SendAutoReply as SendAutoReplyJob;
use App\Listeners\SendAutoReply;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\SendLog;
use App\Models\Thread;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendAutoReplyListenerTest extends TestCase
{
    public function test_listener_handle_method_exists(): void
    {
        $conversation = new Conversation(['id' => 1, 'imported' => true]);
        $thread = new Thread(['id' => 2]);
        $customer = new Customer(['id' => 3]);
        
        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        
        $this->assertTrue(method_exists($listener, 'handle'));
    }

    public function test_listener_has_correct_check_period_constant(): void
    {
        $this->assertEquals(180, SendAutoReply::CHECK_PERIOD);
    }
}