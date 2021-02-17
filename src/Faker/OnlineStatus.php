<?php

namespace RTippin\MessengerFaker\Faker;

use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\MessengerFaker\Broadcasting\OnlineStatusBroadcast;

class OnlineStatus extends MessengerFaker
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
     * OnlineStatus constructor.
     *
     * @param Messenger $messenger
     * @param BroadcastDriver $broadcaster
     */
    public function __construct(Messenger $messenger, BroadcastDriver $broadcaster)
    {
        $this->messenger = $messenger;
        $this->broadcaster = $broadcaster;
        $this->messenger->setOnlineStatus(true);
        $this->messenger->setOnlineCacheLifetime(1);
    }

    /**
     * @param string $status
     * @param MessengerProvider|null $provider
     */
    public function execute(string $status, MessengerProvider $provider = null): void
    {
        if (! is_null($provider)) {
            $this->setStatus($status, $provider);
        } elseif ($this->useOnlyAdmins) {
            $this->thread->participants()->admins()->each(function (Participant $participant) use ($status) {
                $this->setStatus($status, $participant->owner);
            });
        } else {
            $this->thread->participants()->each(function (Participant $participant) use ($status) {
                $this->setStatus($status, $participant->owner);
            });
        }
    }

    /**
     * @param string $status
     * @param MessengerProvider $provider
     */
    private function setStatus(string $status, MessengerProvider $provider): void
    {
        $online = 0;

        switch ($status) {
            case 'online':
                $this->messenger->setProviderToOnline($provider);
                $online = 1;
            break;
            case 'away':
                $this->messenger->setProviderToAway($provider);
                $online = 2;
            break;
            case 'offline':
                $this->messenger->setProviderToOffline($provider);
            break;
        }

        $this->broadcaster
            ->toPresence($this->thread)
            ->with([
                'provider_id' => $provider->getKey(),
                'provider_alias' => $this->messenger->findProviderAlias($provider),
                'name' => $provider->name(),
                'online_status' => $online,
            ])
            ->broadcast(OnlineStatusBroadcast::class);
    }
}
