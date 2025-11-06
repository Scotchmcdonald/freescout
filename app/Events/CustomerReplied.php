<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Thread;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerReplied
{
    use Dispatchable, SerializesModels;

    public Conversation $conversation;
    public Thread $thread;
    public Customer $customer;

    /**
     * Create a new event instance.
     */
    public function __construct(Conversation $conversation, Thread $thread, Customer $customer)
    {
        $this->conversation = $conversation;
        $this->thread = $thread;
        $this->customer = $customer;
    }
}
