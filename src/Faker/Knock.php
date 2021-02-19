<?php

namespace RTippin\MessengerFaker\Faker;

use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Actions\Threads\SendKnock;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Exceptions\KnockException;
use RTippin\MessengerFaker\MessengerFaker;

class Knock
{
    /**
     * @var MessengerFaker
     */
    private MessengerFaker $faker;

    /**
     * @var SendKnock
     */
    private SendKnock $sendKnock;

    /**
     * Knock constructor.
     *
     * @param MessengerFaker $faker
     * @param SendKnock $sendKnock
     */
    public function __construct(MessengerFaker $faker, SendKnock $sendKnock)
    {
        $this->faker = $faker;
        $this->sendKnock = $sendKnock;
    }

    /**
     * @throws FeatureDisabledException
     * @throws InvalidArgumentException
     * @throws InvalidProviderException
     * @throws KnockException
     */
    public function execute(): void
    {
        if ($this->faker->getThread()->isGroup()) {
            $this->faker->setProvider($this->faker->getThread()->participants->first()->owner);

            $this->sendKnock->execute($this->faker->getThread());
        } else {
            $this->faker->setProvider($this->faker->getThread()->participants->first()->owner);

            $this->sendKnock->execute($this->faker->getThread());

            $this->faker->setProvider($this->faker->getThread()->participants->last()->owner);

            $this->sendKnock->execute($this->faker->getThread());
        }
    }
}
