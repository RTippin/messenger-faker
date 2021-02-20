<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class UnReadCommandTest extends MessengerFakerTestCase
{
    /** @test */
    public function unread_command_does_not_find_thread()
    {
        $this->artisan('messenger:faker:unread', [
            'thread' => 404,
        ])
            ->expectsOutput('Thread not found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function unread_command_group_participants_unread()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:unread', [
            'thread' => $group->id,
        ])
            ->expectsOutput('Finished marking participants in First Test Group as unread!')
            ->assertExitCode(0);
    }

    /** @test */
    public function unread_command_private_participants_unread()
    {
        $private = $this->createPrivateThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:unread', [
            'thread' => $private->id,
        ])
            ->expectsOutput('Finished marking participants in Richard Tippin and John Doe as unread!')
            ->assertExitCode(0);
    }
}
