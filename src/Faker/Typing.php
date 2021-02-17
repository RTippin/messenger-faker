<?php

namespace RTippin\MessengerFaker\Faker;

use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\MessengerFaker\Broadcasting\TypingBroadcast;

class Typing extends MessengerFaker
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * @var OnlineStatus
     */
    private OnlineStatus $status;

    /**
     * Typing constructor.
     *
     * @param Messenger $messenger
     * @param BroadcastDriver $broadcaster
     * @param OnlineStatus $status
     */
    public function __construct(Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                OnlineStatus $status)
    {
        $this->messenger = $messenger;
        $this->broadcaster = $broadcaster;
        $this->status = $status;
    }

    /**
     * @param MessengerProvider|null $provider
     */
    public function execute(MessengerProvider $provider = null): void
    {
        $this->status->setThread($this->thread);

        if (! is_null($provider)) {
            $this->sendTyping($provider);
        } elseif ($this->useOnlyAdmins) {
            $this->thread->participants()->admins()->each(fn (Participant $participant) => $this->sendTyping($participant->owner));
        } else {
            $this->thread->participants()->each(fn (Participant $participant) => $this->sendTyping($participant->owner));
        }
    }

    /**
     * @param MessengerProvider $provider
     */
    private function sendTyping(MessengerProvider $provider): void
    {
        $this->status->execute('online', $provider);

        $this->broadcaster
            ->toPresence($this->thread)
            ->with([
                'provider_id' => $provider->getKey(),
                'provider_alias' => $this->messenger->findProviderAlias($provider),
                'name' => $provider->name(),
                'typing' => true,
            ])
            ->broadcast(TypingBroadcast::class);
    }
}
