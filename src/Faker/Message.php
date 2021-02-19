<?php

namespace RTippin\MessengerFaker\Faker;

use Faker\Generator;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Support\Collection;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Participant;
use Throwable;

class Message extends MessengerFakerBase
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var Generator
     */
    private Generator $faker;

    /**
     * @var Typing
     */
    private Typing $typing;

    /**
     * @var Read
     */
    private Read $read;

    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * @var int
     */
    private int $delay;

    /**
     * @var DBCollection
     */
    private DBCollection $participants;

    /**
     * @var Collection
     */
    private Collection $usedParticipants;

    /**
     * Message constructor.
     *
     * @param Messenger $messenger
     * @param Generator $faker
     * @param Typing $typing
     * @param Read $read
     * @param StoreMessage $storeMessage
     */
    public function __construct(Messenger $messenger,
                                Generator $faker,
                                Typing $typing,
                                Read $read,
                                StoreMessage $storeMessage)
    {
        $this->messenger = $messenger;
        $this->faker = $faker;
        $this->typing = $typing;
        $this->read = $read;
        $this->storeMessage = $storeMessage;
        $this->usedParticipants = new Collection([]);
    }

    /**
     * @param int $delay
     * @param bool $admins
     * @return $this
     */
    public function setup(int $delay, bool $admins = false): self
    {
        $this->delay = $delay;
        $this->typing->setThread($this->thread);
        $this->read->setThread($this->thread);
        $this->storeMessage->withoutEvents();

        if ($admins) {
            $this->participants = $this->thread->participants()->admins()->get();
        } else {
            $this->participants = $this->thread->participants()->get();
        }

        return $this;
    }

    /**
     * @param bool $isFinal
     * @throws InvalidProviderException
     * @throws Throwable
     */
    public function execute(bool $isFinal = false): void
    {
        /** @var Participant $participant */
        $participant = $this->participants->random();
        $this->usedParticipants->push($participant);
        $this->messenger->setProvider($participant->owner);
        $this->typing->execute($participant->owner);

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
            $this->read();
        }
    }

    /**
     * Mark all used participants as read.
     */
    private function read(): void
    {
        $this->read->setLatestMessage();

        $this->usedParticipants
            ->unique('owner_id')
            ->each(fn (Participant $participant) => $this->read->execute($participant));
    }
}
