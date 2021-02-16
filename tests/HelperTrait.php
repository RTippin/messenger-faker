<?php

namespace RTippin\MessengerFaker\Tests;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Models\Thread;
use RTippin\MessengerFaker\Tests\Fixtures\UserModel;
use RTippin\MessengerFaker\Tests\Fixtures\UserModelUuid;

trait HelperTrait
{
    /**
     * @return MessengerProvider|UserModel|UserModelUuid
     */
    protected function userTippin()
    {
        return $this->getModelUser()::where('email', '=', 'richard.tippin@gmail.com')->first();
    }

    /**
     * @return MessengerProvider|UserModel|UserModelUuid
     */
    protected function userDoe()
    {
        return $this->getModelUser()::where('email', '=', 'doe@example.net')->first();
    }

    protected function createPrivateThread(MessengerProvider $one, MessengerProvider $two, bool $pending = false): Thread
    {
        $private = Thread::create(Definitions::DefaultThread);

        $private->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => $one->getKey(),
                'owner_type' => get_class($one),
                'pending' => $pending,
            ]));

        $private->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => $two->getKey(),
                'owner_type' => get_class($two),
            ]));

        return $private;
    }

    protected function createGroupThread(MessengerProvider $admin, ...$participants): Thread
    {
        $group = Thread::create([
            'type' => 2,
            'subject' => 'First Test Group',
            'image' => '5.png',
            'add_participants' => true,
            'invitations' => true,
            'calling' => true,
            'knocks' => true,
            'messaging' => true,
            'lockout' => false,
        ]);

        $group->participants()
            ->create(array_merge(Definitions::DefaultAdminParticipant, [
                'owner_id' => $admin->getKey(),
                'owner_type' => get_class($admin),
            ]));

        foreach ($participants as $participant) {
            $group->participants()
                ->create(array_merge(Definitions::DefaultParticipant, [
                    'owner_id' => $participant->getKey(),
                    'owner_type' => get_class($participant),
                ]));
        }

        return $group;
    }
}
