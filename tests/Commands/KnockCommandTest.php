<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use RTippin\Messenger\Brokers\NullBroadcastBroker;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class KnockCommandTest extends MessengerFakerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setBroadcastDriver(NullBroadcastBroker::class);
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
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:knock', [
            'thread' => $group->id,
        ])
            ->expectsOutput('Finished sending knocks to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_knocks_at_private()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:knock', [
            'thread' => $private->id,
        ])
            ->expectsOutput('Finished sending knocks to Richard Tippin and John Doe!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_knocks_at_random_thread_if_id_not_given()
    {
        Participant::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->count(3)->create();

        $this->artisan('messenger:faker:knock')->assertExitCode(0);
    }
}
