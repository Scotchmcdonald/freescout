<?php

namespace App\Jobs;

use App\Mail\ConversationReplyNotification;
use App\Models\Conversation;
use App\Models\Thread;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendConversationReply implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Conversation $conversation,
        public Thread $thread,
        public string $recipientEmail
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->recipientEmail)
            ->send(new ConversationReplyNotification($this->conversation, $this->thread));
    }
}
