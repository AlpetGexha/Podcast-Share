<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmojiReactionEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $listeningPartyId,
        public array $emoji,
        public string|int $userId
    ) {
        //
    }

    public function broadcastOn(): Channel
    {
        return new Channel('listening-party.' . $this->listeningPartyId);
    }

    public function broadcastAs(): string
    {
        return 'emoji-reaction';
    }

    public function broadcastWith(): array
    {
        return [
            'emoji' => $this->emoji,
            'userId' => $this->userId,
        ];
    }
}
