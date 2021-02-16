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
     * @var int
     */
    protected int $delay = 1;

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
     * @param int $delay
     * @return $this
     */
    public function setDelay(int $delay): self
    {
        $this->delay = $delay;

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
}
