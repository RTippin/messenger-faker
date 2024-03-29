<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use RTippin\Messenger\Brokers\NullBroadcastBroker;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class ReadCommandTest extends MessengerFakerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setBroadcastDriver(NullBroadcastBroker::class);
    }

    /** @test */
    public function it_does_not_find_thread()
    {
        $this->artisan('messenger:faker:read', [
            'thread' => 404,
        ])
            ->expectsOutput('Thread not found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_marks_group_read()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:read', [
            'thread' => $group->id,
        ])
            ->expectsOutput('Found First Test Group, now marking participants as read')
            ->expectsOutput('Finished sending mark participants read to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_marks_private_read()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:read', [
            'thread' => $private->id,
        ])
            ->expectsOutput('Found Richard Tippin and John Doe, now marking participants as read')
            ->expectsOutput('Finished sending mark participants read to Richard Tippin and John Doe!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_marks_random_thread_read_if_id_not_given()
    {
        Participant::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->count(3)->create();

        $this->artisan('messenger:faker:read')->assertExitCode(0);
    }
}
