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
     * @var bool
     */
    protected bool $useOnlyAdmins = false;

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
     * @return $this
     */
    public function useOnlyAdmins(): self
    {
        $this->useOnlyAdmins = true;

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
