<?php

namespace App\Console\Commands;

use App\Events\CustomerCreatedConversation;
use App\Events\CustomerReplied;
use App\Models\Conversation;
use App\Models\Thread;
use Illuminate\Console\Command;

class TestEventSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:test-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the event system by manually firing events';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing event system...');

        // Find an existing conversation and thread to test with
        $conversation = Conversation::with(['threads', 'customer'])->first();

        if (! $conversation) {
            $this->error('No conversations found. Run freescout:fetch-emails first.');

            return 1;
        }

        /** @var \App\Models\Thread|null $thread */
        $thread = $conversation->threads()->first();
        $customer = $conversation->customer;

        // @phpstan-ignore-next-line - PHPDoc type hint causes false positive for null check
        if (! $thread || ! $customer) {
            $this->error('Conversation missing thread or customer.');

            return 1;
        }

        $this->info("Testing with Conversation ID: {$conversation->id}");
        $this->info("Customer: {$customer->getMainEmail()}");

        // Test CustomerCreatedConversation event
        $this->info('Firing CustomerCreatedConversation event...');
        event(new CustomerCreatedConversation($conversation, $thread, $customer));

        // Test CustomerReplied event
        $this->info('Firing CustomerReplied event...');
        event(new CustomerReplied($conversation, $thread, $customer));

        $this->info('Events dispatched. Check storage/logs/laravel.log for listener output.');

        return 0;
    }
}
