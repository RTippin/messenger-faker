<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use RTippin\Messenger\Facades\Messenger;
use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class OnlineStatusCommandTest extends MessengerFakerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setBroadcastDriver('null');
    }

    /** @test */
    public function it_does_not_find_thread()
    {
        $this->artisan('messenger:faker:status', [
            'thread' => 404,
        ])
            ->expectsOutput('Thread not found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sets_group_participants_online_by_default()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:status', [
            'thread' => $group->id,
        ])
            ->expectsOutput('Finished marking participants in First Test Group to online!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sets_private_participants_online_default()
    {
        $private = $this->createPrivateThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:status', [
            'thread' => $private->id,
        ])
            ->expectsOutput('Finished marking participants in Richard Tippin and John Doe to online!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sets_participants_to_away()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:status', [
            'thread' => $group->id,
            '--status' => 'away',
        ])
            ->expectsOutput('Finished marking participants in First Test Group to away!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sets_participants_to_offline()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:status', [
            'thread' => $group->id,
            '--status' => 'offline',
        ])
            ->expectsOutput('Finished marking participants in First Test Group to offline!')
            ->assertExitCode(0);
    }
}
