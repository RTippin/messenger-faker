<?php

namespace RTippin\MessengerFaker;

use Exception;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Support\Arr;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Message;
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
        return [
            Message::VIDEO_CALL,
            Message::THREAD_ARCHIVED,
        ];
    }

    /**
     * @return int[]
     */
    private function getAllowedTypesGroup(): array
    {
        return [
            Message::PARTICIPANT_JOINED_WITH_INVITE,
            Message::VIDEO_CALL,
            Message::GROUP_AVATAR_CHANGED,
            Message::THREAD_ARCHIVED,
            Message::GROUP_CREATED,
            Message::GROUP_RENAMED,
            Message::DEMOTED_ADMIN,
            Message::PROMOTED_ADMIN,
            Message::PARTICIPANT_LEFT_GROUP,
            Message::PARTICIPANT_REMOVED,
            Message::PARTICIPANTS_ADDED,
            Message::BOT_ADDED,
            Message::BOT_RENAMED,
            Message::BOT_AVATAR_CHANGED,
            Message::BOT_REMOVED,
        ];
    }

    /**
     * Messages started.
     *
     * @param  int|null  $type
     * @return array
     *
     * @throws Exception
     */
    private function generateSystemMessage(?int $type): array
    {
        $type = $this->getType($type);
        $participant = $this->participants->random();

        return $this->makeBody($type, $participant);
    }

    /**
     * @param  int|null  $type
     * @return int
     *
     * @throws Exception
     */
    private function getType(?int $type): int
    {
        if (is_null($type)) {
            return Arr::random(
                $this->thread->isGroup()
                    ? $this->getAllowedTypesGroup()
                    : $this->getAllowedTypesPrivate(),
                1
            )[0];
        }

        if ($this->thread->isGroup() && ! in_array($type, $this->getAllowedTypesGroup())) {
            throw new Exception('Invalid system message type.');
        }

        if ($this->thread->isPrivate() && ! in_array($type, $this->getAllowedTypesPrivate())) {
            throw new Exception('Invalid system message type for private thread.');
        }

        return $type;
    }

    /**
     * @param  int  $type
     * @param  Participant  $participant
     * @return array
     *
     * @throws Exception
     */
    private function makeBody(int $type, Participant $participant): array
    {
        switch ($type) {
            case Message::PARTICIPANT_JOINED_WITH_INVITE: return $this->makeJoinedWithInvite($participant);
            case Message::VIDEO_CALL: return $this->makeVideoCall($participant);
            case Message::GROUP_AVATAR_CHANGED: return $this->makeGroupAvatarChanged($participant);
            case Message::THREAD_ARCHIVED: return $this->makeThreadArchived($participant);
            case Message::GROUP_CREATED: return $this->makeGroupCreated($participant);
            case Message::GROUP_RENAMED: return $this->makeGroupRenamed($participant);
            case Message::DEMOTED_ADMIN: return $this->makeParticipantDemoted($participant);
            case Message::PROMOTED_ADMIN: return $this->makeParticipantPromoted($participant);
            case Message::PARTICIPANT_LEFT_GROUP: return $this->makeGroupLeft($participant);
            case Message::PARTICIPANT_REMOVED: return $this->makeRemovedFromGroup($participant);
            case Message::PARTICIPANTS_ADDED: return $this->makeParticipantsAdded($participant);
            case Message::BOT_ADDED: return $this->makeBotAdded($participant);
            case Message::BOT_RENAMED: return $this->makeBotRenamed($participant);
            case Message::BOT_AVATAR_CHANGED: return $this->makeBotAvatarChanged($participant);
            case Message::BOT_REMOVED: return $this->makeBotRemoved($participant);
        }

        throw new Exception('Invalid system message type.');
    }

    /**
     * @param  Participant  $participant
     * @return array
     */
    private function makeJoinedWithInvite(Participant $participant): array
    {
        return MessageTransformer::makeJoinedWithInvite($this->thread, $participant->owner);
    }

    /**
     * @param  Participant  $participant
     * @return array
     */
    private function makeVideoCall(Participant $participant): array
    {
        $call = Call::factory()->for($this->thread)->owner($participant->owner)->ended()->create();
        CallParticipant::factory()->for($call)->owner($participant->owner)->left()->create();

        $this->participants
            ->reject(fn (Participant $p) => $p->id === $participant->id)
            ->shuffle()
            ->take(rand(0, $this->participants->count() - 1))
            ->each(
                fn (Participant $p) => CallParticipant::factory()
                    ->for($call)
                    ->owner($p->owner)
                    ->left()
                    ->create()
            );

        return MessageTransformer::makeVideoCall($this->thread, $participant->owner, $call);
    }

    /**
     * @param  Participant  $participant
     * @return array
     */
    private function makeGroupAvatarChanged(Participant $participant): array
    {
        return MessageTransformer::makeGroupAvatarChanged($this->thread, $participant->owner);
    }

    /**
     * @param  Participant  $participant
     * @return array
     */
    private function makeThreadArchived(Participant $participant): array
    {
        return MessageTransformer::makeThreadArchived($this->thread, $participant->owner);
    }

    /**
     * @param  Participant  $participant
     * @return array
     */
    private function makeGroupCreated(Participant $participant): array
    {
        return MessageTransformer::makeGroupCreated($this->thread, $participant->owner, $this->faker->catchPhrase);
    }

    /**
     * @param  Participant  $participant
     * @return array
     */
    private function makeGroupRenamed(Participant $participant): array
    {
        return MessageTransformer::makeGroupRenamed($this->thread, $participant->owner, $this->faker->catchPhrase);
    }

    /**
     * @param  Participant  $participant
     * @return array
     *
     * @throws Exception
     */
    private function makeParticipantDemoted(Participant $participant): array
    {
        $demoted = $this->participants
            ->reject(fn (Participant $p) => $p->id === $participant->id)
            ->shuffle()
            ->take(1);

        if (! $demoted->count()) {
            throw new Exception('No other participants to choose from.');
        }

        return MessageTransformer::makeParticipantDemoted($this->thread, $participant->owner, $demoted->first());
    }

    /**
     * @param  Participant  $participant
     * @return array
     *
     * @throws Exception
     */
    private function makeParticipantPromoted(Participant $participant): array
    {
        $promoted = $this->participants
            ->reject(fn (Participant $p) => $p->id === $participant->id)
            ->shuffle()
            ->take(1);

        if (! $promoted->count()) {
            throw new Exception('No other participants to choose from.');
        }

        return MessageTransformer::makeParticipantPromoted($this->thread, $participant->owner, $promoted->first());
    }

    /**
     * @param  Participant  $participant
     * @return array
     */
    private function makeGroupLeft(Participant $participant): array
    {
        return MessageTransformer::makeGroupLeft($this->thread, $participant->owner);
    }

    /**
     * @param  Participant  $participant
     * @return array
     *
     * @throws Exception
     */
    private function makeRemovedFromGroup(Participant $participant): array
    {
        $removed = $this->participants
            ->reject(fn (Participant $p) => $p->id === $participant->id)
            ->shuffle()
            ->take(1);

        if (! $removed->count()) {
            throw new Exception('No other participants to choose from.');
        }

        return MessageTransformer::makeRemovedFromGroup($this->thread, $participant->owner, $removed->first());
    }

    /**
     * @param  Participant  $participant
     * @return array
     *
     * @throws Exception
     */
    private function makeParticipantsAdded(Participant $participant): array
    {
        $added = $this->participants
            ->reject(fn (Participant $p) => $p->id === $participant->id)
            ->shuffle()
            ->take(rand(1, $this->participants->count() - 1));

        if (! $added->count()) {
            throw new Exception('No other participants to choose from.');
        }

        return MessageTransformer::makeParticipantsAdded($this->thread, $participant->owner, $added);
    }

    /**
     * @param  Participant  $participant
     * @return array
     */
    private function makeBotAdded(Participant $participant): array
    {
        return MessageTransformer::makeBotAdded($this->thread, $participant->owner, $this->faker->firstName);
    }

    /**
     * @param  Participant  $participant
     * @return array
     */
    private function makeBotRenamed(Participant $participant): array
    {
        return MessageTransformer::makeBotRenamed($this->thread, $participant->owner, $this->faker->firstName, $this->faker->firstName);
    }

    /**
     * @param  Participant  $participant
     * @return array
     */
    private function makeBotAvatarChanged(Participant $participant): array
    {
        return MessageTransformer::makeBotAvatarChanged($this->thread, $participant->owner, $this->faker->firstName);
    }

    /**
     * @param  Participant  $participant
     * @return array
     */
    private function makeBotRemoved(Participant $participant): array
    {
        return MessageTransformer::makeBotRemoved($this->thread, $participant->owner, $this->faker->firstName);
    }
}
