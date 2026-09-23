<?php

namespace App\Notifications;

use App\Models\TouristSpot;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SpotRevisionRequested extends Notification
{
    use Queueable;

    public function __construct(
        public TouristSpot $touristSpot,
        public string $reason,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tourist_spot_id' => $this->touristSpot->id,
            'tourist_spot_name' => $this->touristSpot->name,
            'reason' => $this->reason,
            'message' => 'A Super Admin requested revisions for this tourist spot.',
            'url' => route('tourist_spots.edit', $this->touristSpot),
        ];
    }
}