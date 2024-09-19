<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public $listeningPartyId,
        public string $message
    ) {
        //
    }

    public function broadcastOn(): Channel
    {
        return new Channel('listening-party.' . $this->listeningPartyId);
    }

    public function broadcastAs(): string
    {
        return 'new-message';
    }
}
