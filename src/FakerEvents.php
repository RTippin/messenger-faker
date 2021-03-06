<?php

namespace RTippin\MessengerFaker;

use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Support\Collection;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use Throwable;

/**
 * @property-read Collection $usedParticipants;
 * @property-read DBCollection $participants;
 * @property-read ?Thread $thread;
 * @property-read Messenger $messenger;
 * @property-read BroadcastDriver $broadcaster;
 */
trait FakerEvents
{
    /**
     * Messages started.
     * @throws Throwable
     */
    private function startMessage(): void
    {
        /** @var Participant $participant */
        $participant = $this->participants->random();
        $this->usedParticipants->push($participant);
        $this->composer()->from($participant->owner)->emitTyping();

        if ($this->delay > 0) {
            sleep(1);
        }
    }

    /**
     * Messages ended.
     *
     * @param bool $isFinal
     */
    private function endMessage(bool $isFinal): void
    {
        if (! $isFinal) {
            sleep($this->delay);
        } else {
            $this->usedParticipants
                ->unique('owner_id')
                ->uniqueStrict(fn (Participant $participant) => $participant->owner_type.$participant->owner_id)
                ->each(fn (Participant $participant) => $this->read($participant));
        }
    }
}
