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
    public ?array $senderInfo;
    /** Populated when a Customer model is passed to the constructor. */
    public ?Customer $customer;

    /**
     * Create a new event instance.
     *
     * @param  Customer|array|null  $senderInfo  Either a Customer model (which will be
     *                                           transformed to an array internally) or a
     *                                           plain associative array with 'email' / 'name'.
     */
    public function __construct(Conversation $conversation, Thread $thread, Customer|array|null $senderInfo = null)
    {
        $this->conversation = $conversation;
        $this->thread = $thread;

        if ($senderInfo instanceof Customer) {
            $this->customer = $senderInfo;
            $this->senderInfo = [
                'email' => $senderInfo->getMainEmail() ?? $conversation->customer_email,
                'name' => $senderInfo->getFullName(),
            ];
        } else {
            $this->customer = null;
            $this->senderInfo = $senderInfo ?? ['email' => $conversation->customer_email];
        }
    }
}
