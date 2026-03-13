<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendConversationReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Conversation $conversation,
        public \App\Models\Thread $thread
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Reload thread to check current state
        $this->thread->refresh();

        // Check if thread is still valid for sending
        // Must be Published (2) and Message type (1)
        if ($this->thread->state !== \App\Models\Thread::STATE_PUBLISHED || $this->thread->type !== \App\Models\Thread::TYPE_MESSAGE) {
            return;
        }

        // Ensure conversation has mailbox loaded
        if (! $this->conversation->relationLoaded('mailbox')) {
            $this->conversation->load('mailbox');
        }

        $customerEmail = $this->conversation->customer_email;

        if ($customerEmail) {
            Mail::to($customerEmail)
                ->send(new \App\Mail\CustomerReply($this->conversation, $this->thread));
        }
    }
}
