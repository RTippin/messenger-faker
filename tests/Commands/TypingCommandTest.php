<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use RTippin\Messenger\Facades\Messenger;
use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class TypingCommandTest extends MessengerFakerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setBroadcastDriver('null');
    }

    /** @test */
    public function typing_command_does_not_find_thread()
    {
        $this->artisan('messenger:faker:typing', [
            'thread' => 404,
        ])
            ->expectsOutput('Thread not found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function typing_command_group_participants_type()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:typing', [
            'thread' => $group->id,
        ])
            ->expectsOutput('Finished making participants in First Test Group type!')
            ->assertExitCode(0);
    }

    /** @test */
    public function typing_command_private_participants_type()
    {
        $private = $this->createPrivateThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:typing', [
            'thread' => $private->id,
        ])
            ->expectsOutput('Finished making participants in Richard Tippin and John Doe type!')
            ->assertExitCode(0);
    }
}
