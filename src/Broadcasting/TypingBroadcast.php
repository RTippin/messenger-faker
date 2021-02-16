<?php

namespace RTippin\MessengerFaker\Broadcasting;

use RTippin\Messenger\Broadcasting\MessengerBroadcast;

class TypingBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'client-typing';
    }
}
