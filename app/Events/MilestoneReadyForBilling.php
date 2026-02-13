<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Milestone;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MilestoneReadyForBilling extends VersionedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Milestone $milestone)
    {
        parent::__construct($milestone);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [];
    }
}
