<?php

namespace RTippin\MessengerFaker\Faker;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use RTippin\Messenger\Models\Thread;

abstract class MessengerFaker
{
    /**
     * @var Thread
     */
    protected Thread $thread;

    /**
     * @param string $threadId
     * @return $this
     * @throws ModelNotFoundException
     */
    public function setThread(string $threadId): self
    {
        $this->thread = Thread::findOrFail($threadId);

        return $this;
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
     * @param int $delay
     * @return mixed
     */
    public abstract function execute($delay = 0);
}
