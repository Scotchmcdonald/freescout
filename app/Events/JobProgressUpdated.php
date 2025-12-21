<?php

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

    public $userId;
    public $jobId;
    public $name;
    public $progress;
    public $statusText;
    public $status; // 'started', 'processing', 'completed'

    /**
     * Create a new event instance.
     */
    public function __construct($userId, $jobId, $name, $progress, $statusText, $status = 'processing')
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

    public function broadcastAs()
    {
        return 'job.updated';
    }
}
