<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TouristSpotChanged implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $action,
        public array $spot
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('tourist-spots');
    }

    public function broadcastAs(): string
    {
        return 'tourist-spot.changed';
    }
}
