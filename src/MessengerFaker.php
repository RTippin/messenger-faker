<?php

namespace RTippin\MessengerFaker;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Actions\Threads\SendKnock;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Exceptions\KnockException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;

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
     * @var SendKnock
     */
    private SendKnock $sendKnock;

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
     * @param SendKnock $sendKnock
     */
    public function __construct(Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                SendKnock $sendKnock)
    {
        $this->messenger = $messenger;
        $this->broadcaster = $broadcaster;
        $this->sendKnock = $sendKnock;

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
}