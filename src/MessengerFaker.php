<?php

namespace RTippin\MessengerFaker;

use Exception;
use Faker\Generator;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
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
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\MessengerFaker\Broadcasting\OnlineStatusBroadcast;
use RTippin\MessengerFaker\Broadcasting\ReadBroadcast;
use RTippin\MessengerFaker\Broadcasting\TypingBroadcast;
use Throwable;

class MessengerFaker
{
    const DefaultImageURL = 'https://source.unsplash.com/random';

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
    private bool $isTesting = false;

    /**
     * MessengerFaker constructor.
     *
     * @param Messenger $messenger
     * @param BroadcastDriver $broadcaster
     * @param Generator $faker
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
        $this->markRead = $markRead;
        $this->faker = $faker;
        $this->storeMessage = $storeMessage;
        $this->storeImage = $storeImage;
        $this->storeDocument = $storeDocument;
        $this->storeAudio = $storeAudio;
        $this->delay = 0;
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
     * @throws InvalidProviderException
     * @throws InvalidArgumentException
     * @throws FeatureDisabledException
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
     * @throws Throwable|InvalidProviderException
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
     * @throws FeatureDisabledException
     * @throws InvalidProviderException
     * @throws Throwable
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
     * @throws FeatureDisabledException
     * @throws InvalidProviderException
     * @throws Throwable
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
     * @throws FeatureDisabledException
     * @throws InvalidProviderException
     * @throws Throwable
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
     * @throws InvalidProviderException
     */
    private function startMessage(): void
    {
        /** @var Participant $participant */
        $participant = $this->participants->random();
        $this->usedParticipants->push($participant);
        $this->setProvider($participant->owner);
        $this->typing($participant->owner);

        if ($this->delay > 0) {
            sleep(1);
        }
    }

    /**
     * @param bool $isFinal
     */
    private function endMessage(bool $isFinal = false): void
    {
        if (! $isFinal) {
            sleep($this->delay);
        } else {
            $this->usedParticipants
                ->unique('owner_id')
                ->each(fn (Participant $participant) => $this->read($participant));
        }
    }

    /**
     * @param bool $local
     * @param string|null $url
     * @return array
     * @throws Exception
     */
    private function getImage(bool $local, ?string $url): array
    {
        if ($this->isTesting) {
            return [UploadedFile::fake()->image('test.jpg'), 'test.jpg'];
        }

        if ($local) {
            $path = config('messenger-faker.paths.images');
            $images = File::files($path);
            if (! count($images)) {
                $this->throwFailedException("No images found within {$path}");
            }
            $file = Arr::random($images, 1)[0];
            $name = $file->getFilename();
        } else {
            $name = uniqid();
            $file = '/tmp/'.$name;
            file_put_contents($file, file_get_contents(is_null($url) ? self::DefaultImageURL : $url));
        }

        return [new UploadedFile($file, $name), $file];
    }

    /**
     * @param string|null $url
     * @return array
     * @throws Exception
     */
    private function getDocument(?string $url): array
    {
        if ($this->isTesting) {
            return [UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'), 'test.pdf'];
        }

        if (! is_null($url)) {
            $name = uniqid();
            $file = '/tmp/'.$name;
            file_put_contents($file, file_get_contents($url));
        } else {
            $path = config('messenger-faker.paths.documents');
            $documents = File::files($path);
            if (! count($documents)) {
                $this->throwFailedException("No documents found within {$path}");
            }
            $file = Arr::random($documents, 1)[0];
            $name = $file->getFilename();
        }

        return [new UploadedFile($file, $name), $file];
    }

    /**
     * @param string|null $url
     * @return array
     * @throws Exception
     */
    private function getAudio(?string $url): array
    {
        if ($this->isTesting) {
            return [UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'), 'test.mp3'];
        }

        if (! is_null($url)) {
            $name = uniqid();
            $file = '/tmp/'.$name;
            file_put_contents($file, file_get_contents($url));
        } else {
            $path = config('messenger-faker.paths.audio');
            $audio = File::files($path);
            if (! count($audio)) {
                $this->throwFailedException("No audio found within {$path}");
            }
            $file = Arr::random($audio, 1)[0];
            $name = $file->getFilename();
        }

        return [new UploadedFile($file, $name), $file];
    }

    /**
     * @param string $file
     */
    private function unlinkFile(string $file): void
    {
        if (! $this->isTesting) {
            unlink($file);
        }
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
     * @param string $status
     * @param MessengerProvider $provider
     */
    private function setStatus(string $status, MessengerProvider $provider): void
    {
        $online = 0;

        switch ($status) {
            case 'online':
                $this->messenger->setProviderToOnline($provider);
                $online = 1;
            break;
            case 'away':
                $this->messenger->setProviderToAway($provider);
                $online = 2;
            break;
            case 'offline':
                $this->messenger->setProviderToOffline($provider);
            break;
        }

        $this->broadcaster
            ->toPresence($this->thread)
            ->with([
                'provider_id' => $provider->getKey(),
                'provider_alias' => $this->messenger->findProviderAlias($provider),
                'name' => $provider->name(),
                'online_status' => $online,
            ])
            ->broadcast(OnlineStatusBroadcast::class);
    }

    /**
     * @param Participant $participant
     * @param Message $message
     */
    private function markRead(Participant $participant, Message $message): void
    {
        $this->markRead->withoutDispatches()->execute($participant);

        $this->broadcaster
            ->toPresence($this->thread)
            ->with([
                'provider_id' => $participant->owner_id,
                'provider_alias' => $this->messenger->findProviderAlias($participant->owner_type),
                'message_id' => $message->id,
            ])
            ->broadcast(ReadBroadcast::class);
    }

    /**
     * @param MessengerProvider $provider
     */
    private function sendTyping(MessengerProvider $provider): void
    {
        $this->status('online', $provider);

        $this->broadcaster
            ->toPresence($this->thread)
            ->with([
                'provider_id' => $provider->getKey(),
                'provider_alias' => $this->messenger->findProviderAlias($provider),
                'name' => $provider->name(),
                'typing' => true,
            ])
            ->broadcast(TypingBroadcast::class);
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
