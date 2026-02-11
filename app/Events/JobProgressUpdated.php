<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public string $jobId;
    public string $name;
    public int $progress;
    public string $statusText;
    public string $status; // 'started', 'processing', 'completed'

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, string $jobId, string $name, int $progress, string $statusText, string $status = 'processing')
    {
        $this->userId = $userId;
        $this->jobId = $jobId;
        $this->name = $name;
        $this->progress = $progress;
        $this->statusText = $statusText;
        $this->status = $status;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'job.updated';
    }
}
