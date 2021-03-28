<?php

namespace RTippin\MessengerFaker;

use Exception;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Support\Arr;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\Definitions;

/**
 * @property-read DBCollection $participants;
 * @property-read ?Thread $thread;
 */
trait FakerSystemMessages
{
    /**
     * @return int[]
     */
    private function getAllowedTypesPrivate(): array
    {
        return [90, 92];
    }

    /**
     * @return int[]
     */
    private function getAllowedTypesGroup(): array
    {
        return [88, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99];
    }

    /**
     * Messages started.
     * @param int|null $type
     * @return array
     * @throws Exception
     */
    private function generateSystemMessage(?int $type): array
    {
        $type = $this->getType($type);
        /** @var Participant $participant */
        $participant = $this->participants->random();

        return [
            $this->thread,
            $participant->owner,
            $this->getBody($type, $participant),
            Definitions::Message[$type],
        ];
    }

    /**
     * @param int|null $type
     * @return int
     * @throws Exception
     */
    private function getType(?int $type): int
    {
        if ($this->thread->isGroup()) {
            if (is_null($type)) {
                return Arr::random($this->getAllowedTypesGroup(), 1)[0];
            } elseif (! in_array($type, $this->getAllowedTypesGroup())) {
                $this->throwFailedException('Invalid system message type.');
            }
        } else {
            if (is_null($type)) {
                return Arr::random($this->getAllowedTypesPrivate(), 1)[0];
            } elseif (! in_array($type, $this->getAllowedTypesPrivate())) {
                $this->throwFailedException('Invalid system message type.');
            }
        }

        return $type;
    }

    /**
     * @param int $type
     * @param Participant $participant
     * @return string
     */
    private function getBody(int $type, Participant $participant): string
    {
        switch ($type) {
            case 88: return $this->makeJoinedWithInvite();
        }

        return '';
    }

    /**
     * @return string
     */
    private function makeJoinedWithInvite(): string
    {
        return 'joined';
    }
}
