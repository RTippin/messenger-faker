<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class OnlineStatusCommandTest extends MessengerFakerTestCase
{
    /** @test */
    public function status_command_does_not_find_thread()
    {
        $this->artisan('messenger:faker:status', [
            'thread' => 404,
        ])
            ->expectsOutput('Thread not found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function status_command_group_participants_default_online()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:status', [
            'thread' => $group->id,
        ])
            ->expectsOutput('Finished marking participants in First Test Group to online!')
            ->assertExitCode(0);
    }

    /** @test */
    public function status_command_private_participants_default_online()
    {
        $private = $this->createPrivateThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:status', [
            'thread' => $private->id,
        ])
            ->expectsOutput('Finished marking participants in Richard Tippin and John Doe to online!')
            ->assertExitCode(0);
    }

    /** @test */
    public function status_command_participants_to_away()
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
    public function status_command_participants_to_offline()
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
