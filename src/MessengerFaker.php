<?php

namespace RTippin\MessengerFaker;

use Exception;
use Faker\Generator;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Messages\StoreSystemMessage;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Exceptions\MessengerComposerException;
use RTippin\Messenger\Exceptions\ReactionException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\MessengerComposer;
use Throwable;

class MessengerFaker
{
    use FakerEvents,
        FakerFiles,
        FakerSystemMessages;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var MessengerComposer
     */
    private MessengerComposer $composer;

    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * @var Generator
     */
    private Generator $faker;

    /**
     * @var StoreSystemMessage
     */
    private StoreSystemMessage $storeSystem;

    /**
     * @var Thread|null
     */
    private ?Thread $thread = null;

    /**
     * @var DBCollection
     */
    private DBCollection $participants;

    /**
     * @var DBCollection|null
     */
    private ?DBCollection $messages = null;

    /**
     * @var Collection
     */
    private Collection $usedParticipants;

    /**
     * @var int
     */
    private int $delay = 0;

    /**
     * @var bool
     */
    private static bool $isTesting = false;

    /**
     * MessengerFaker constructor.
     *
     * @param Messenger $messenger
     * @param MessengerComposer $composer
     * @param BroadcastDriver $broadcaster
     * @param Generator $faker
     * @param StoreSystemMessage $storeSystem
     */
    public function __construct(Messenger $messenger,
                                MessengerComposer $composer,
                                BroadcastDriver $broadcaster,
                                Generator $faker,
                                StoreSystemMessage $storeSystem)
    {
        $this->messenger = $messenger;
        $this->composer = $composer;
        $this->broadcaster = $broadcaster;
        $this->faker = $faker;
        $this->storeSystem = $storeSystem;
        $this->usedParticipants = new Collection([]);
        $this->messenger->setKnockKnock(true);
        $this->messenger->setKnockTimeout(0);
        $this->messenger->setMessageReactions(true);
        $this->messenger->setSystemMessages(true);
    }

    /**
     * Set testing to true, so we may fake file uploads and remove delays.
     */
    public static function testing(): void
    {
        static::$isTesting = true;

        BaseMessengerAction::disableEvents();
    }

    /**
     * @return Generator
     */
    public function getFakerGenerator(): Generator
    {
        return $this->faker;
    }

    /**
     * @param string|null $threadId
     * @param bool $useAdmins
     * @return $this
     * @throws ModelNotFoundException
     */
    public function setThreadWithId(?string $threadId = null, bool $useAdmins = false): self
    {
        $this->thread = is_null($threadId)
            ? Thread::inRandomOrder()->firstOrFail()
            : Thread::findOrFail($threadId);

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
     * @param int $count
     * @return $this
     * @throws Exception
     */
    public function setMessages(int $count = 5): self
    {
        if ($this->thread->messages()->nonSystem()->count() < $count) {
            $this->throwFailedException("{$this->getThreadName()} does not have $count or more messages to choose from.");
        }

        $this->messages = $this->thread->messages()->nonSystem()->latest()->with('owner')->limit($count)->get();

        return $this;
    }

    /**
     * @param int $delay
     * @return $this
     */
    public function setDelay(int $delay): self
    {
        if (! static::$isTesting) {
            $this->delay = $delay;
        }

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
            : "{$this->participants->first()->owner->getProviderName()} and {$this->participants->last()->owner->getProviderName()}";
    }

    /**
     * Send a knock to the given thread.
     *
     * @return $this
     * @throws FeatureDisabledException|InvalidProviderException
     * @throws Throwable
     */
    public function knock(): self
    {
        $this->composer()->from($this->participants->first()->owner)->knock();

        if ($this->thread->isPrivate()) {
            $this->composer()->from($this->participants->last()->owner)->knock();
        }

        return $this;
    }

    /**
     * Mark the given providers as read and send broadcast.
     *
     * @param Participant|null $participant
     * @return $this
     * @throws Throwable
     */
    public function read(Participant $participant = null): self
    {
        if (! is_null($message = $this->thread->messages()->latest()->first())) {
            if (! is_null($participant)) {
                $this->composer()
                    ->from($participant->owner)
                    ->emitRead($message)
                    ->read($participant);
            } else {
                $this->participants->each(function (Participant $participant) use ($message) {
                    $this->composer()
                        ->from($participant->owner)
                        ->emitRead($message)
                        ->read($participant);
                });
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
     * @throws Throwable
     */
    public function typing(MessengerProvider $provider = null): self
    {
        if (! is_null($provider)) {
            $this->composer()->from($provider)->emitTyping();
        } else {
            $this->participants->each(fn (Participant $participant) => $this->composer()->from($participant->owner)->emitTyping());
        }

        return $this;
    }

    /**
     * Send messages using the given providers and show typing and mark read.
     *
     * @param bool $isFinal
     * @return $this
     * @throws Throwable
     */
    public function message(bool $isFinal = false): self
    {
        $this->startMessage();

        if (rand(0, 100) > 80) {
            $message = '';
            for ($x = 0; $x < rand(1, 10); $x++) {
                $message .= $this->faker->emoji;
            }
        } else {
            $message = $this->faker->realText(rand(10, 200), rand(1, 4));
        }

        $this->composer()->message($message);

        $this->endMessage($isFinal);

        return $this;
    }

    /**
     * Send image messages using the given providers and show typing and mark read.
     *
     * @param bool $isFinal
     * @param bool $local
     * @param string|null $url
     * @return $this
     * @throws Throwable
     */
    public function image(bool $isFinal = false,
                          bool $local = false,
                          ?string $url = null): self
    {
        $this->startMessage();

        $image = $this->getImage($local, $url);

        $this->composer()->image($image[0]);

        $this->endMessage($isFinal);

        if (! $local) {
            $this->unlinkFile($image[1]);
        }

        return $this;
    }

    /**
     * Send document messages using the given providers and show typing and mark read.
     *
     * @param bool $isFinal
     * @param string|null $url
     * @return $this
     * @throws Throwable
     */
    public function document(bool $isFinal = false, ?string $url = null): self
    {
        $this->startMessage();

        $document = $this->getDocument($url);

        $this->composer()->document($document[0]);

        $this->endMessage($isFinal);

        if (! is_null($url)) {
            $this->unlinkFile($document[1]);
        }

        return $this;
    }

    /**
     * Send audio messages using the given providers and show typing and mark read.
     *
     * @param bool $isFinal
     * @param string|null $url
     * @return $this
     * @throws Throwable
     */
    public function audio(bool $isFinal = false, ?string $url = null): self
    {
        $this->startMessage();

        $audio = $this->getAudio($url);

        $this->composer()->audio($audio[0]);

        $this->endMessage($isFinal);

        if (! is_null($url)) {
            $this->unlinkFile($audio[1]);
        }

        return $this;
    }

    /**
     * @param int|null $type
     * @param bool $isFinal
     * @return $this
     * @throws Throwable
     */
    public function system(?int $type = null, bool $isFinal = false): self
    {
        $this->storeSystem->execute(...$this->generateSystemMessage($type));

        if (! $isFinal) {
            sleep($this->delay);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws FeatureDisabledException|Throwable
     * @throws Throwable
     */
    public function reaction(bool $isFinal = false): self
    {
        try {
            $this->composer()
                ->from($this->participants->random()->owner)
                ->reaction($this->messages->random(), $this->faker->emoji);
        } catch (ReactionException $e) {
            // continue as it may pick duplicate random emoji
        }

        if (! $isFinal) {
            sleep($this->delay);
        }

        return $this;
    }

    /**
     * @return MessengerComposer
     * @throws MessengerComposerException
     */
    private function composer(): MessengerComposer
    {
        return $this->composer->to($this->thread);
    }

    /**
     * @param bool $useAdmins
     */
    private function setParticipants(bool $useAdmins): void
    {
        if ($useAdmins && $this->thread->isGroup()) {
            $this->participants = $this->thread->participants()->admins()->with('owner')->get();
        } else {
            $this->participants = $this->thread->participants()->with('owner')->get();
        }
    }

    /**
     * @param string $message
     * @throws Exception
     */
    private function throwFailedException(string $message): void
    {
        throw new Exception($message);
    }
}
