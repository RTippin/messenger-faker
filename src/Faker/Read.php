<?php

namespace RTippin\MessengerFaker\Faker;

use RTippin\Messenger\Actions\Threads\MarkParticipantRead;
use RTippin\Messenger\Contracts\BroadcastDriver;

class Read extends MessengerFaker
{
    /**
     * @var MarkParticipantRead
     */
    private MarkParticipantRead $markRead;

    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * Read constructor.
     *
     * @param MarkParticipantRead $markRead
     * @param BroadcastDriver $broadcaster
     */
    public function __construct(MarkParticipantRead $markRead, BroadcastDriver $broadcaster)
    {
        $this->markRead = $markRead;
        $this->broadcaster = $broadcaster;
    }

    public function execute($delay = 0)
    {
        //TODO
    }
}
