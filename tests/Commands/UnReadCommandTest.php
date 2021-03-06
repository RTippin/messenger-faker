<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use RTippin\Messenger\Brokers\NullBroadcastBroker;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class UnReadCommandTest extends MessengerFakerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setBroadcastDriver(NullBroadcastBroker::class);
    }

    /** @test */
    public function it_does_not_find_thread()
    {
        $this->artisan('messenger:faker:unread', [
            'thread' => 404,
        ])
            ->expectsOutput('Thread not found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_marks_group_participants_unread()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:unread', [
            'thread' => $group->id,
        ])
            ->expectsOutput('Finished marking participants in First Test Group as unread!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_marks_private_participants_unread()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:unread', [
            'thread' => $private->id,
        ])
            ->expectsOutput('Finished marking participants in Richard Tippin and John Doe as unread!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_makes_participants_unread_in_a_random_thread_if_id_not_given()
    {
        Participant::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->count(3)->create();

        $this->artisan('messenger:faker:unread')->assertExitCode(0);
    }
}
