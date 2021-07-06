<?php

namespace RTippin\MessengerFaker;

use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Support\Collection;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\MessengerFaker\Broadcasting\ReadBroadcast;
use RTippin\MessengerFaker\Broadcasting\TypingBroadcast;

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
     */
    private function startMessage(): void
    {
        /** @var Participant $participant */
        $participant = $this->participants->random();
        $this->usedParticipants->push($participant);
        $this->setProvider($participant->owner);
        $this->typing($participant->owner);

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

    /**
     * @param Participant $participant
     * @param Message $message
     */
    private function markRead(Participant $participant, Message $message): void
    {
        $this->markRead->withoutDispatches()->execute($participant);

        $this->broadcaster
            ->toPresence($this->thread)
            ->with([
                'provider_id' => $participant->owner_id,
                'provider_alias' => $this->messenger->findProviderAlias($participant->owner_type),
                'message_id' => $message->id,
            ])
            ->broadcast(ReadBroadcast::class);
    }

    /**
     * @param MessengerProvider $provider
     */
    private function sendTyping(MessengerProvider $provider): void
    {
        $this->broadcaster
            ->toPresence($this->thread)
            ->with([
                'provider_id' => $provider->getKey(),
                'provider_alias' => $this->messenger->findProviderAlias($provider),
                'name' => $provider->getProviderName(),
                'typing' => true,
            ])
            ->broadcast(TypingBroadcast::class);
    }
}
