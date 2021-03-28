<?php

namespace RTippin\MessengerFaker;

use Exception;
use Faker\Generator;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Actions\Messages\StoreAudioMessage;
use RTippin\Messenger\Actions\Messages\StoreDocumentMessage;
use RTippin\Messenger\Actions\Messages\StoreImageMessage;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Actions\Threads\MarkParticipantRead;
use RTippin\Messenger\Actions\Threads\SendKnock;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Exceptions\KnockException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use Throwable;

class MessengerFaker
{
    use FakerEvents;
    use FakerFiles;

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
     * @var ConfigRepository
     */
    private ConfigRepository $configRepo;

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
     * @var StoreImageMessage
     */
    private StoreImageMessage $storeImage;

    /**
     * @var StoreDocumentMessage
     */
    private StoreDocumentMessage $storeDocument;

    /**
     * @var StoreAudioMessage
     */
    private StoreAudioMessage $storeAudio;

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
     * @var bool
     */
    private bool $isTesting;

    /**
     * MessengerFaker constructor.
     *
     * @param Messenger $messenger
     * @param BroadcastDriver $broadcaster
     * @param Generator $faker
     * @param ConfigRepository $configRepo
     * @param SendKnock $sendKnock
     * @param MarkParticipantRead $markRead
     * @param StoreMessage $storeMessage
     * @param StoreImageMessage $storeImage
     * @param StoreDocumentMessage $storeDocument
     * @param StoreAudioMessage $storeAudio
     */
    public function __construct(Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                Generator $faker,
                                ConfigRepository $configRepo,
                                SendKnock $sendKnock,
                                MarkParticipantRead $markRead,
                                StoreMessage $storeMessage,
                                StoreImageMessage $storeImage,
                                StoreDocumentMessage $storeDocument,
                                StoreAudioMessage $storeAudio)
    {
        $this->messenger = $messenger;
        $this->broadcaster = $broadcaster;
        $this->sendKnock = $sendKnock;
        $this->configRepo = $configRepo;
        $this->markRead = $markRead;
        $this->faker = $faker;
        $this->storeMessage = $storeMessage;
        $this->storeImage = $storeImage;
        $this->storeDocument = $storeDocument;
        $this->storeAudio = $storeAudio;
        $this->delay = 0;
        $this->isTesting = false;
        $this->usedParticipants = new Collection([]);
        $this->messenger->setKnockKnock(true);
        $this->messenger->setKnockTimeout(0);
        $this->messenger->setOnlineStatus(true);
        $this->messenger->setOnlineCacheLifetime(1);
    }

    /**
     * @return $this
     */
    public function fake(): self
    {
        $this->isTesting = true;

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
        if (! $this->isTesting) {
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
            : "{$this->participants->first()->owner->name()} and {$this->participants->last()->owner->name()}";
    }

    /**
     * Send a knock to the given thread.
     *
     * @return $this
     * @throws FeatureDisabledException|InvalidArgumentException|InvalidProviderException
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
     * @throws Throwable
     */
    public function message(bool $isFinal = false): self
    {
        $this->startMessage();
        $this->storeMessage->execute(
            $this->thread,
            [
                'message' => $this->faker->realText(rand(10, 200), rand(1, 4)),
            ]
        );
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
     * @throws Throwable|FeatureDisabledException
     */
    public function image(bool $isFinal = false,
                          bool $local = false,
                          ?string $url = null): self
    {
        $this->startMessage();
        $image = $this->getImage($local, $url);
        $this->storeImage->execute(
            $this->thread,
            [
                'image' => $image[0],
            ]
        );
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
     * @throws Throwable|FeatureDisabledException
     */
    public function document(bool $isFinal = false, ?string $url = null): self
    {
        $this->startMessage();
        $document = $this->getDocument($url);
        $this->storeDocument->execute(
            $this->thread,
            [
                'document' => $document[0],
            ]
        );
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
     * @throws Throwable|FeatureDisabledException
     */
    public function audio(bool $isFinal = false, ?string $url = null): self
    {
        $this->startMessage();
        $audio = $this->getAudio($url);
        $this->storeAudio->execute(
            $this->thread,
            [
                'audio' => $audio[0],
            ]
        );
        $this->endMessage($isFinal);

        if (! is_null($url)) {
            $this->unlinkFile($audio[1]);
        }

        return $this;
    }

    /**
     * @param bool $isFinal
     * @param int|null $type
     * @return $this
     */
    public function system(bool $isFinal = false, ?int $type = null): self
    {
        return $this;
    }

    /**
     * @param MessengerProvider|mixed|null $provider
     * @return $this
     * @throws InvalidProviderException
     */
    private function setProvider($provider = null): self
    {
        $this->messenger->setProvider($provider);

        return $this;
    }

    /**
     * @param bool $useAdmins
     */
    private function setParticipants(bool $useAdmins): void
    {
        if ($useAdmins && $this->thread->isGroup()) {
            $this->participants = $this->thread->participants()->admins()->get();
        } else {
            $this->participants = $this->thread->participants()->get();
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
