<?php

namespace RTippin\MessengerFaker\Faker;

class UnRead extends MessengerFaker
{
    /**
     * Reset specified last_read on participants
     */
    public function execute(): void
    {
        if ($this->useOnlyAdmins && $this->thread->isGroup()) {
            $this->thread->participants()->admins()->update([
                'last_read' => null,
            ]);
        } else {
            $this->thread->participants()->update([
                'last_read' => null,
            ]);
        }
    }
}
