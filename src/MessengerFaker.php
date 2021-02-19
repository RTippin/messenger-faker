<?php

namespace RTippin\MessengerFaker;

use Faker\Generator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Actions\Threads\MarkParticipantRead;
use RTippin\Messenger\Actions\Threads\SendKnock;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Exceptions\KnockException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\MessengerFaker\Broadcasting\OnlineStatusBroadcast;
use RTippin\MessengerFaker\Broadcasting\ReadBroadcast;
use RTippin\MessengerFaker\Broadcasting\TypingBroadcast;

class MessengerFaker
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
     * @var Generator
     */
    private Generator $faker;

    /**
     * @var SendKnock
     */
    private SendKnock $sendKnock;

    /**
     * @var MarkParticipantRead
     */
    private MarkParticipantRead $markRead;

    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * @var Thread|null
     */
    private ?Thread $thread = null;

    /**
     * @var bool
     */
    private bool $useOnlyAdmins;

    /**
     * @var int
     */
    private int $delay;

    /**
     * MessengerFaker constructor.
     *
     * @param Messenger $messenger
     * @param BroadcastDriver $broadcaster
     * @param Generator $faker
     * @param SendKnock $sendKnock
     * @param MarkParticipantRead $markRead
     * @param StoreMessage $storeMessage
     */
    public function __construct(Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                Generator $faker,
                                SendKnock $sendKnock,
                                MarkParticipantRead $markRead,
                                StoreMessage $storeMessage)
    {
        $this->messenger = $messenger;
        $this->broadcaster = $broadcaster;
        $this->sendKnock = $sendKnock;
        $this->markRead = $markRead;
        $this->faker = $faker;
        $this->storeMessage = $storeMessage;
        $this->useOnlyAdmins = false;
        $this->delay = 0;
        $this->messenger->setKnockKnock(true);
        $this->messenger->setKnockTimeout(0);
        $this->messenger->setOnlineStatus(true);
        $this->messenger->setOnlineCacheLifetime(1);
    }

    /**
     * @param MessengerProvider|mixed|null $provider
     * @return $this
     * @throws InvalidProviderException
     */
    public function setProvider($provider = null): self
    {
        $this->messenger->setProvider($provider);

        return $this;
    }

    /**
     * @param string $threadId
     * @return $this
     * @throws ModelNotFoundException
     */
    public function setThreadWithId(string $threadId): self
    {
        $this->thread = Thread::findOrFail($threadId);

        return $this;
    }

    /**
     * @param Thread $thread
     * @return $this
     */
    public function setThread(Thread $thread): self
    {
        $this->thread = $thread;

        return $this;
    }

    /**
     * @param bool $useAdmins
     * @return $this
     */
    public function useAdmins(bool $useAdmins = true): self
    {
        $this->useOnlyAdmins = $useAdmins;

        return $this;
    }

    /**
     * @return Thread|null
     */
    public function getThread(): ?Thread
    {
        return $this->thread;
    }

    /**
     * @return Thread
     */
    public function getThreadName(): string
    {
        return $this->thread->isGroup()
            ? $this->thread->name()
            : "{$this->thread->participants->first()->owner->name()} and {$this->thread->participants->last()->owner->name()}";
    }

    /**
     * Send a knock to the given thread.
     *
     * @return $this
     * @throws InvalidProviderException
     * @throws InvalidArgumentException
     * @throws FeatureDisabledException
     * @throws KnockException
     */
    public function knock(): self
    {
        if ($this->thread->isGroup()) {
            $this->setProvider($this->thread->participants->first()->owner);

            $this->sendKnock->execute($this->thread);
        } else {
            $this->setProvider($this->thread->participants->first()->owner);

            $this->sendKnock->execute($this->thread);

            $this->setProvider($this->thread->participants->last()->owner);

            $this->sendKnock->execute($this->thread);
        }

        return $this;
    }

    /**
     * Set and broadcast the given providers online status.
     *
     * @param string $status
     * @param MessengerProvider|null $provider
     * @return $this
     */
    public function status(string $status, MessengerProvider $provider = null): self
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

        return $this;
    }

    /**
     * Mark the given providers as read and send broadcast.
     *
     * @param Participant|null $participant
     * @return $this
     */
    public function read(Participant $participant = null): self
    {
        if (! is_null($message = $this->thread->messages()->latest()->first())) {
            if (! is_null($participant)) {
                $this->markRead($participant, $message);
            } elseif ($this->useOnlyAdmins && $this->thread->isGroup()) {
                $this->thread->participants()->admins()->each(fn (Participant $participant) => $this->markRead($participant, $message));
            } else {
                $this->thread->participants()->each(fn (Participant $participant) => $this->markRead($participant, $message));
            }
        }

        return $this;
    }

    /**
     * Mark the given providers as unread.
     *
     * @return $this
     */
    public function unread(): self
    {
        if ($this->useOnlyAdmins && $this->thread->isGroup()) {
            $this->thread->participants()->admins()->update([
                'last_read' => null,
            ]);
        } else {
            $this->thread->participants()->update([
                'last_read' => null,
            ]);
        }

        return $this;
    }

    /**
     * Make the given providers send typing broadcast.
     *
     * @param MessengerProvider|null $provider
     * @return $this
     */
    public function typing(MessengerProvider $provider = null): self
    {
        $this->messenger->setOnlineCacheLifetime(0);

        if (! is_null($provider)) {
            $this->sendTyping($provider);
        } elseif ($this->useOnlyAdmins) {
            $this->thread->participants()->admins()->each(fn (Participant $participant) => $this->sendTyping($participant->owner));
        } else {
            $this->thread->participants()->each(fn (Participant $participant) => $this->sendTyping($participant->owner));
        }

        $this->messenger->setOnlineCacheLifetime(1);

        return $this;
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
        $this->status('online', $provider);

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