<?php

namespace RTippin\MessengerFaker\Faker;

use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Actions\Threads\SendKnock;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Exceptions\KnockException;
use RTippin\Messenger\Messenger;

class Knock extends MessengerFaker
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
     * Knock constructor.
     *
     * @param Messenger $messenger
     * @param SendKnock $sendKnock
     */
    public function __construct(Messenger $messenger, SendKnock $sendKnock)
    {
        $this->messenger = $messenger;
        $this->sendKnock = $sendKnock;
        $this->messenger->setKnockKnock(true);
        $this->messenger->setKnockTimeout(0);
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
