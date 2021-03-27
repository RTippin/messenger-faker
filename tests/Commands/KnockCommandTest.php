<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use RTippin\Messenger\Facades\Messenger;
use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class KnockCommandTest extends MessengerFakerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setBroadcastDriver('null');
    }

    /** @test */
    public function it_does_not_find_thread()
    {
        $this->artisan('messenger:faker:knock', [
            'thread' => 404,
        ])
            ->expectsOutput('Thread not found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_knocks_at_group()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:knock', [
            'thread' => $group->id,
        ])
            ->expectsOutput('Finished knocking at First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_knocks_at_private()
    {
        $private = $this->createPrivateThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:knock', [
            'thread' => $private->id,
        ])
            ->expectsOutput('Finished knocking at Richard Tippin and John Doe!')
            ->assertExitCode(0);
    }
}
