<?php

namespace RTippin\MessengerFaker\Faker;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Actions\Threads\SendKnock;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Exceptions\KnockException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;

class Knock
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var SendKnock
     */
    private SendKnock $sendKnock;

    /**
     * @var Thread
     */
    private Thread $thread;

    /**
     * Knock constructor.
     *
     * @param Messenger $messenger
     * @param SendKnock $sendKnock
     */
    public function __construct(Messenger $messenger, SendKnock $sendKnock)
    {
        $this->messenger = $messenger;
        $this->sendKnock = $sendKnock;
    }

    /**
     * @param string $threadId
     * @return void
     * @throws ModelNotFoundException
     */
    public function setup(string $threadId): void
    {
        $this->thread = Thread::findOrFail($threadId)->load('participants.owner');

        $this->messenger->setKnockKnock(true);

        $this->messenger->setKnockTimeout(0);
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
     * @throws InvalidProviderException|InvalidArgumentException|FeatureDisabledException|KnockException
     */
    public function execute(): void
    {
        if ($this->thread->isGroup()) {
            $this->messenger->setProvider($this->thread->participants->first()->owner);

            $this->sendKnock->execute($this->thread);
        } else {
            $this->messenger->setProvider($this->thread->participants->first()->owner);

            $this->sendKnock->execute($this->thread);

            $this->messenger->setProvider($this->thread->participants->last()->owner);

            $this->sendKnock->execute($this->thread);
        }
    }
}
