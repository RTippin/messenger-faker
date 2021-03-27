<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use RTippin\Messenger\Facades\Messenger;
use RTippin\MessengerFaker\MessengerFaker;
use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class MessageCommandTest extends MessengerFakerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setBroadcastDriver('null');
        app(MessengerFaker::class)->fake();
    }

    /** @test */
    public function it_does_not_find_thread()
    {
        $this->artisan('messenger:faker:message', [
            'thread' => 404,
        ])
            ->expectsOutput('Thread not found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_default_of_5_messages_to_group()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:message', [
            'thread' => $group->id,
        ])
            ->expectsOutput('Found First Test Group, now messaging...')
            ->expectsOutput('Finished sending 5 messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_default_of_5_messages_to_private()
    {
        $private = $this->createPrivateThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:message', [
            'thread' => $private->id,
        ])
            ->expectsOutput('Found Richard Tippin and John Doe, now messaging...')
            ->expectsOutput('Finished sending 5 messages to Richard Tippin and John Doe!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_message_count()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:message', [
            'thread' => $group->id,
            '--count' => 2,
        ])
            ->expectsOutput('Found First Test Group, now messaging...')
            ->expectsOutput('Finished sending 2 messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_zero_message_count()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:message', [
            'thread' => $group->id,
            '--count' => 0,
        ])
            ->expectsOutput('Found First Test Group, now messaging...')
            ->expectsOutput('Finished sending 0 messages to First Test Group!')
            ->assertExitCode(0);
    }
}
