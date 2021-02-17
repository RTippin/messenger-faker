<?php

namespace RTippin\MessengerFaker\Faker;

use RTippin\Messenger\Actions\Threads\MarkParticipantRead;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\MessengerFaker\Broadcasting\ReadBroadcast;

class Read extends MessengerFaker
{
    /**
     * @var MarkParticipantRead
     */
    private MarkParticipantRead $markRead;

    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * @var Message|null
     */
    private ?Message $message = null;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * Read constructor.
     *
     * @param Messenger $messenger
     * @param MarkParticipantRead $markRead
     * @param BroadcastDriver $broadcaster
     */
    public function __construct(Messenger $messenger,
                                MarkParticipantRead $markRead,
                                BroadcastDriver $broadcaster)
    {
        $this->markRead = $markRead;
        $this->broadcaster = $broadcaster;
        $this->messenger = $messenger;
    }

    /**
     * @param Message|null $message
     * @return $this
     */
    public function setLatestMessage(Message $message = null): self
    {
        if (! is_null($message)) {
            $this->message = $message;
        } else {
            $this->message = $this->thread->messages()->latest()->first();
        }

        return $this;
    }

    /**
     * @param Participant|null $participant
     */
    public function execute(Participant $participant = null): void
    {
        if (! is_null($participant)) {
            $this->markRead($participant);
        } elseif ($this->useOnlyAdmins && $this->thread->isGroup()) {
            $this->thread->participants()->admins()->each(fn (Participant $participant) => $this->markRead($participant));
        } else {
            $this->thread->participants()->each(fn (Participant $participant) => $this->markRead($participant));
        }
    }

    /**
     * @param Participant $participant
     */
    private function markRead(Participant $participant): void
    {
        $this->markRead->withoutDispatches()->execute($participant);

        if (! is_null($this->message)) {
            $this->broadcaster
                ->toPresence($this->thread)
                ->with([
                    'provider_id' => $participant->owner_id,
                    'provider_alias' => $this->messenger->findProviderAlias($participant->owner_type),
                    'message_id' => $this->message->id,
                ])
                ->broadcast(ReadBroadcast::class);
        }
    }
}
