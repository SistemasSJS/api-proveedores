<?php

namespace App\Events;

use App\Models\Requisicion;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequisicionStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Requisicion $requisicion,
        public string $previousStatus
    ) {}
}
