<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use RTippin\Messenger\Brokers\NullBroadcastBroker;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class RandomCommandTest extends MessengerFakerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setBroadcastDriver(NullBroadcastBroker::class);
    }

    /** @test */
    public function it_does_not_find_thread()
    {
        $this->artisan('messenger:faker:random', [
            'thread' => 404,
        ])
            ->expectsOutput('Thread not found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_default_of_5_random_actions_to_group()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);
        Message::factory()->for($group)->owner($this->tippin)->count(5)->create();

        $this->artisan('messenger:faker:random', [
            'thread' => $group->id,
        ])
            ->expectsOutput('Found First Test Group, now sending random actions...')
            ->expectsOutput('Finished sending 5 random actions to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_default_of_5_reactions_to_private()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);
        Message::factory()->for($private)->owner($this->tippin)->count(5)->create();

        $this->artisan('messenger:faker:random', [
            'thread' => $private->id,
        ])
            ->expectsOutput('Found Richard Tippin and John Doe, now sending random actions...')
            ->expectsOutput('Finished sending 5 random actions to Richard Tippin and John Doe!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_actions_to_random_thread_if_id_not_given()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);
        $group = $this->createGroupThread($this->tippin);
        Message::factory()->for($private)->owner($this->tippin)->count(5)->create();
        Message::factory()->for($group)->owner($this->tippin)->count(5)->create();

        $this->artisan('messenger:faker:random')->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_random_actions_count()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);
        Message::factory()->for($group)->owner($this->tippin)->count(2)->create();

        $this->artisan('messenger:faker:random', [
            'thread' => $group->id,
            '--count' => 2,
        ])
            ->expectsOutput('Found First Test Group, now sending random actions...')
            ->expectsOutput('Finished sending 2 random actions to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_zero_random_actions_count()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:random', [
            'thread' => $group->id,
            '--count' => 0,
        ])
            ->expectsOutput('Found First Test Group, now sending random actions...')
            ->expectsOutput('Finished sending 0 random actions to First Test Group!')
            ->assertExitCode(0);
    }
}
