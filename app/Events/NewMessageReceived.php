<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversation;
use App\Models\Thread;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Thread $thread,
        public Conversation $conversation,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('mailbox.'.$this->conversation->mailbox_id),
        ];

        // Broadcast to all users in the mailbox
        /** @var \Illuminate\Database\Eloquent\Builder<\App\Models\User> $usersQuery */
        $usersQuery = $this->conversation->mailbox->users();
        $users = $usersQuery->pluck('users.id');
        foreach ($users as $userId) {
            if (is_int($userId) || is_string($userId)) {
                $channels[] = new PrivateChannel('user.'.(string) $userId);
            }
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.new';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'thread_id' => $this->thread->id,
            'conversation_id' => $this->conversation->id,
            'conversation_number' => $this->conversation->number,
            'conversation_subject' => $this->conversation->subject,
            'thread_type' => $this->thread->type,
            'from' => $this->thread->from,
            'preview' => mb_substr(strip_tags($this->thread->body ?? ''), 0, 100),
            'customer_name' => $this->thread->customer?->getFullName(),
            'user_name' => $this->thread->user?->getFullName(),
            'mailbox_id' => $this->conversation->mailbox_id,
            'mailbox_name' => $this->conversation->mailbox->name,
            'created_at' => $this->thread->created_at?->toISOString(),
        ];
    }
}
