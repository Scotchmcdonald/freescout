<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\ConversationReplyNotification;
use App\Models\Conversation;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendConversationReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param  Conversation  $conversation
     * @param  array<mixed>  $replies
     * @param  Customer  $customer
     */
    public function __construct(
        public Conversation $conversation,
        public array $replies,
        public Customer $customer
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!empty($this->replies)) {
             $lastReply = end($this->replies);
             
             // Ensure conversation has mailbox loaded
             if (!$this->conversation->relationLoaded('mailbox')) {
                 $this->conversation->load('mailbox');
             }
             
             if ($lastReply instanceof \App\Models\Thread) {
                 $customerEmail = $this->customer->getMainEmail();
                 if ($customerEmail) {
                     Mail::to($customerEmail)
                        ->send(new ConversationReplyNotification($this->conversation, $lastReply));
                 }
             }
        }
    }
}
