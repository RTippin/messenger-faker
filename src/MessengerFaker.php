<?php

namespace RTippin\MessengerFaker;

use Faker\Generator;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
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
use Throwable;

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
     * @var DBCollection
     */
    private DBCollection $participants;

    /**
     * @var Collection
     */
    private Collection $usedParticipants;

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
        $this->delay = 0;
        $this->usedParticipants = new Collection([]);
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
     * @param bool $useAdmins
     * @return $this
     * @throws ModelNotFoundException
     */
    public function setThreadWithId(string $threadId, bool $useAdmins = false): self
    {
        $this->thread = Thread::findOrFail($threadId);

        $this->setParticipants($useAdmins);

        return $this;
    }

    /**
     * @param Thread $thread
     * @param bool $useAdmins
     * @return $this
     * @throws ModelNotFoundException
     */
    public function setThread(Thread $thread, bool $useAdmins = false): self
    {
        $this->thread = $thread;

        $this->setParticipants($useAdmins);

        return $this;
    }

    /**
     * @param int $delay
     * @return $this
     */
    public function setDelay(int $delay): self
    {
        $this->delay = $delay;

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
            : "{$this->participants->first()->owner->name()} and {$this->participants->last()->owner->name()}";
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
            $this->setProvider($this->participants->first()->owner);

            $this->sendKnock->execute($this->thread);
        } else {
            $this->setProvider($this->participants->first()->owner);

            $this->sendKnock->execute($this->thread);

            $this->setProvider($this->participants->last()->owner);

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
        } else {
            $this->participants->each(function (Participant $participant) use ($status) {
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
            } else {
                $this->participants->each(fn (Participant $participant) => $this->markRead($participant, $message));
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
        $this->participants->each(fn (Participant $participant) => $participant->update(['last_read' => null]));

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
        } else {
            $this->participants->each(fn (Participant $participant) => $this->sendTyping($participant->owner));
        }

        $this->messenger->setOnlineCacheLifetime(1);

        return $this;
    }

    /**
     * Send messages using the given providers and show typing and mark read.
     *
     * @param bool $isFinal
     * @return $this
     * @throws InvalidProviderException
     * @throws Throwable
     */
    public function message(bool $isFinal = false): self
    {
        /** @var Participant $participant */
        $participant = $this->participants->random();
        $this->usedParticipants->push($participant);
        $this->setProvider($participant->owner);
        $this->typing($participant->owner);

        if ($this->delay > 0) {
            sleep(1);
        }

        $this->storeMessage->execute(
            $this->thread,
            $this->faker->realText(rand(10, 200), rand(1, 4))
        );

        if (! $isFinal) {
            sleep($this->delay);
        } else {
            $this->usedParticipants
                ->unique('owner_id')
                ->each(fn (Participant $participant) => $this->read($participant));
        }

        return $this;
    }

    /**
     * @param bool $useAdmins
     */
    private function setParticipants(bool $useAdmins): void
    {
        if ($useAdmins) {
            $this->participants = $this->thread->participants()->admins()->get();
        } else {
            $this->participants = $this->thread->participants()->get();
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
