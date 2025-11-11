<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  array<string, mixed>|null  $meta
     */
    public function __construct(
        public Conversation $conversation,
        public string $updateType = 'status_changed', // status_changed, assigned, new_thread, etc
        public ?array $meta = null,
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

        // Also broadcast to assigned user if exists
        if ($this->conversation->user_id) {
            $channels[] = new PrivateChannel('user.'.$this->conversation->user_id);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->conversation->id,
            'number' => $this->conversation->number,
            'subject' => $this->conversation->subject,
            'status' => $this->conversation->status,
            'update_type' => $this->updateType,
            'user_id' => $this->conversation->user_id,
            'customer_id' => $this->conversation->customer_id,
            'mailbox_id' => $this->conversation->mailbox_id,
            'meta' => $this->meta,
            'updated_at' => $this->conversation->updated_at?->toISOString(),
        ];
    }
}
