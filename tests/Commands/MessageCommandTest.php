<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use RTippin\Messenger\Facades\Messenger;
use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class MessageCommandTest extends MessengerFakerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setBroadcastDriver('null');
    }

    /** @test */
    public function message_command_does_not_find_thread()
    {
        $this->artisan('messenger:faker:message', [
            'thread' => 404,
        ])
            ->expectsOutput('Thread not found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function message_command_messages_group_default_of_5()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:message', [
            'thread' => $group->id,
            '--delay' => 0,
        ])
            ->expectsOutput('Found First Test Group, now messaging...')
            ->expectsOutput(' (done)')
            ->expectsOutput('Finished sending 5 messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function message_command_messages_private_default_of_5()
    {
        $private = $this->createPrivateThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:message', [
            'thread' => $private->id,
            '--delay' => 0,
        ])
            ->expectsOutput('Found Richard Tippin and John Doe, now messaging...')
            ->expectsOutput(' (done)')
            ->expectsOutput('Finished sending 5 messages to Richard Tippin and John Doe!')
            ->assertExitCode(0);
    }

    /** @test */
    public function message_command_accepts_message_count()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:message', [
            'thread' => $group->id,
            '--count' => 2,
            '--delay' => 0,
        ])
            ->expectsOutput('Found First Test Group, now messaging...')
            ->expectsOutput(' (done)')
            ->expectsOutput('Finished sending 2 messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function message_command_accepts_zero_message_count()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:message', [
            'thread' => $group->id,
            '--count' => 0,
            '--delay' => 0,
        ])
            ->expectsOutput('Found First Test Group, now messaging...')
            ->expectsOutput(' (done)')
            ->expectsOutput('Finished sending 0 messages to First Test Group!')
            ->assertExitCode(0);
    }
}
