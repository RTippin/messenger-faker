<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class ReactCommandTest extends MessengerFakerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setBroadcastDriver('null');
    }

    /** @test */
    public function it_does_not_find_thread()
    {
        $this->artisan('messenger:faker:react', [
            'thread' => 404,
        ])
            ->expectsOutput('Thread not found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_does_not_have_enough_messages_to_react_to()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:react', [
            'thread' => $group->id,
        ])
            ->expectsOutput('First Test Group does not have 5 or more messages to choose from.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_default_of_5_reactions_to_5_messages_in_group()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);
        Message::factory()
            ->for($group)
            ->owner($this->tippin)
            ->count(5)
            ->create();

        $this->artisan('messenger:faker:react', [
            'thread' => $group->id,
        ])
            ->expectsOutput('Found First Test Group, now adding reactions to the 5 most recent messages...')
            ->expectsOutput('Finished sending 5 reactions to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_default_of_5_reactions_to_5_messages_in_private()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);
        Message::factory()
            ->for($private)
            ->owner($this->tippin)
            ->count(5)
            ->create();

        $this->artisan('messenger:faker:react', [
            'thread' => $private->id,
        ])
            ->expectsOutput('Found Richard Tippin and John Doe, now adding reactions to the 5 most recent messages...')
            ->expectsOutput('Finished sending 5 reactions to Richard Tippin and John Doe!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_reactions_and_messages_count()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);
        Message::factory()
            ->for($group)
            ->owner($this->tippin)
            ->count(2)
            ->create();

        $this->artisan('messenger:faker:react', [
            'thread' => $group->id,
            '--count' => 2,
            '--messages' => 2,
        ])
            ->expectsOutput('Found First Test Group, now adding reactions to the 2 most recent messages...')
            ->expectsOutput('Finished sending 2 reactions to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_zero_reactions_and_messages_count()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:react', [
            'thread' => $group->id,
            '--count' => 0,
            '--messages' => 0,
        ])
            ->expectsOutput('Found First Test Group, now adding reactions to the 0 most recent messages...')
            ->expectsOutput('Finished sending 0 reactions to First Test Group!')
            ->assertExitCode(0);
    }
}
