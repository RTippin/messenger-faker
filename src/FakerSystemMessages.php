<?php

namespace RTippin\MessengerFaker;

use Exception;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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
                $this->throwFailedException('Invalid system message type for private thread.');
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
            case 90: return $this->makeVideoCall($participant);
            case 91: return $this->makeGroupAvatarChanged();
            case 92: return $this->makeThreadArchived();
            case 93: return $this->makeGroupCreated();
            case 94: return $this->makeGroupRenamed();
            case 95: return $this->makeParticipantDemoted($participant);
            case 96: return $this->makeParticipantPromoted($participant);
            case 97: return $this->makeGroupLeft();
            case 98: return $this->makeRemovedFromGroup($participant);
            case 99: return $this->makeParticipantsAdded($participant);
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

    /**
     * @param Participant $participant
     * @return string
     */
    private function makeVideoCall(Participant $participant): string
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

        return (new Collection(['call_id' => $call->id]))->toJson();
    }

    /**
     * @return string
     */
    private function makeGroupAvatarChanged(): string
    {
        return 'updated the avatar';
    }

    /**
     * @return string
     */
    private function makeThreadArchived(): string
    {
        return $this->thread->isGroup()
            ? 'archived the group'
            : 'archived the conversation';
    }

    /**
     * @return string
     */
    private function makeGroupCreated(): string
    {
        return "created {$this->faker->catchPhrase}";
    }

    /**
     * @return string
     */
    private function makeGroupRenamed(): string
    {
        return "renamed the group to {$this->faker->catchPhrase}";
    }

    /**
     * @param Participant $participant
     * @return string
     */
    private function makeParticipantDemoted(Participant $participant): string
    {
        $demoted = $this->participants
            ->reject(fn (Participant $p) => $p->id === $participant->id)
            ->shuffle()
            ->take(1);

        if (! $demoted->count()) {
            $this->throwFailedException('No other participants to choose from.');
        }

        return (new Collection([
            'owner_id' => $demoted->first()->owner_id,
            'owner_type' => $demoted->first()->owner_type,
        ]))->toJson();
    }

    /**
     * @param Participant $participant
     * @return string
     */
    private function makeParticipantPromoted(Participant $participant): string
    {
        $promoted = $this->participants
            ->reject(fn (Participant $p) => $p->id === $participant->id)
            ->shuffle()
            ->take(1);

        if (! $promoted->count()) {
            $this->throwFailedException('No other participants to choose from.');
        }

        return (new Collection([
            'owner_id' => $promoted->first()->owner_id,
            'owner_type' => $promoted->first()->owner_type,
        ]))->toJson();
    }

    /**
     * @return string
     */
    private function makeGroupLeft(): string
    {
        return 'left';
    }

    /**
     * @param Participant $participant
     * @return string
     */
    private function makeRemovedFromGroup(Participant $participant): string
    {
        $removed = $this->participants
            ->reject(fn (Participant $p) => $p->id === $participant->id)
            ->shuffle()
            ->take(1);

        if (! $removed->count()) {
            $this->throwFailedException('No other participants to choose from.');
        }

        return (new Collection([
            'owner_id' => $removed->first()->owner_id,
            'owner_type' => $removed->first()->owner_type,
        ]))->toJson();
    }

    /**
     * @param Participant $participant
     * @return string
     */
    private function makeParticipantsAdded(Participant $participant): string
    {
        $added = $this->participants
            ->reject(fn (Participant $p) => $p->id === $participant->id)
            ->shuffle()
            ->take(rand(1, $this->participants->count() - 1));

        if (! $added->count()) {
            $this->throwFailedException('No other participants to choose from.');
        }

        return $added->map(fn ($item) => [
            'owner_id' => $item['owner_id'],
            'owner_type' => $item['owner_type'],
        ])->toJson();
    }
}
