<?php

namespace RTippin\MessengerFaker;

use Exception;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Support\Arr;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\MessageTransformer;

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
        $participant = $this->participants->random();

        return $this->makeBody($type, $participant);
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
                $this->throwFailedException('Invalid system message type for private thread.');
            }
        }

        return $type;
    }

    /**
     * @param int $type
     * @param Participant $participant
     * @return array
     */
    private function makeBody(int $type, Participant $participant): array
    {
        switch ($type) {
            case 88: return $this->makeJoinedWithInvite($participant);
            case 90: return $this->makeVideoCall($participant);
            case 91: return $this->makeGroupAvatarChanged($participant);
            case 92: return $this->makeThreadArchived($participant);
            case 93: return $this->makeGroupCreated($participant);
            case 94: return $this->makeGroupRenamed($participant);
            case 95: return $this->makeParticipantDemoted($participant);
            case 96: return $this->makeParticipantPromoted($participant);
            case 97: return $this->makeGroupLeft($participant);
            case 98: return $this->makeRemovedFromGroup($participant);
            case 99: return $this->makeParticipantsAdded($participant);
            default: $this->throwFailedException('Invalid system message type for private thread.');
        }
    }

    /**
     * @param Participant $participant
     * @return array
     */
    private function makeJoinedWithInvite(Participant $participant): array
    {
        return MessageTransformer::makeJoinedWithInvite($this->thread, $participant->owner);
    }

    /**
     * @param Participant $participant
     * @return array
     */
    private function makeVideoCall(Participant $participant): array
    {
        $call = $this->thread->calls()->create([
            'type' => 1,
            'owner_id' => $participant->owner_id,
            'owner_type' => $participant->owner_type,
            'call_ended' => now(),
            'setup_complete' => true,
            'teardown_complete' => true,
        ]);

        $call->participants()->create([
            'owner_id' => $participant->owner_id,
            'owner_type' => $participant->owner_type,
            'left_call' => now(),
        ]);

        $this->participants
            ->reject(fn (Participant $p) => $p->id === $participant->id)
            ->shuffle()
            ->take(rand(0, $this->participants->count() - 1))
            ->each(function (Participant $p) use ($call) {
                $call->participants()->create([
                    'owner_id' => $p->owner_id,
                    'owner_type' => $p->owner_type,
                    'left_call' => now(),
                ]);
            });

        return MessageTransformer::makeVideoCall($this->thread, $participant->owner, $call);
    }

    /**
     * @param Participant $participant
     * @return array
     */
    private function makeGroupAvatarChanged(Participant $participant): array
    {
        return MessageTransformer::makeGroupAvatarChanged($this->thread, $participant->owner);
    }

    /**
     * @param Participant $participant
     * @return array
     */
    private function makeThreadArchived(Participant $participant): array
    {
        return MessageTransformer::makeThreadArchived($this->thread, $participant->owner);
    }

    /**
     * @param Participant $participant
     * @return array
     */
    private function makeGroupCreated(Participant $participant): array
    {
        return MessageTransformer::makeGroupCreated($this->thread, $participant->owner, $this->faker->catchPhrase);
    }

    /**
     * @param Participant $participant
     * @return array
     */
    private function makeGroupRenamed(Participant $participant): array
    {
        return MessageTransformer::makeGroupRenamed($this->thread, $participant->owner, $this->faker->catchPhrase);
    }

    /**
     * @param Participant $participant
     * @return array
     */
    private function makeParticipantDemoted(Participant $participant): array
    {
        $demoted = $this->participants
            ->reject(fn (Participant $p) => $p->id === $participant->id)
            ->shuffle()
            ->take(1);

        if (! $demoted->count()) {
            $this->throwFailedException('No other participants to choose from.');
        }

        return MessageTransformer::makeParticipantDemoted($this->thread, $participant->owner, $demoted->first());
    }

    /**
     * @param Participant $participant
     * @return array
     */
    private function makeParticipantPromoted(Participant $participant): array
    {
        $promoted = $this->participants
            ->reject(fn (Participant $p) => $p->id === $participant->id)
            ->shuffle()
            ->take(1);

        if (! $promoted->count()) {
            $this->throwFailedException('No other participants to choose from.');
        }

        return MessageTransformer::makeParticipantPromoted($this->thread, $participant->owner, $promoted->first());
    }

    /**
     * @param Participant $participant
     * @return array
     */
    private function makeGroupLeft(Participant $participant): array
    {
        return MessageTransformer::makeGroupLeft($this->thread, $participant->owner);
    }

    /**
     * @param Participant $participant
     * @return array
     */
    private function makeRemovedFromGroup(Participant $participant): array
    {
        $removed = $this->participants
            ->reject(fn (Participant $p) => $p->id === $participant->id)
            ->shuffle()
            ->take(1);

        if (! $removed->count()) {
            $this->throwFailedException('No other participants to choose from.');
        }

        return MessageTransformer::makeRemovedFromGroup($this->thread, $participant->owner, $removed->first());
    }

    /**
     * @param Participant $participant
     * @return array
     */
    private function makeParticipantsAdded(Participant $participant): array
    {
        $added = $this->participants
            ->reject(fn (Participant $p) => $p->id === $participant->id)
            ->shuffle()
            ->take(rand(1, $this->participants->count() - 1));

        if (! $added->count()) {
            $this->throwFailedException('No other participants to choose from.');
        }

        return MessageTransformer::makeParticipantsAdded($this->thread, $participant->owner, $added);
    }
}
