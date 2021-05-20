<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
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
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:status', [
            'thread' => $group->id,
        ])
            ->expectsOutput('Finished marking participants in First Test Group to online!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sets_private_participants_online_default()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:status', [
            'thread' => $private->id,
        ])
            ->expectsOutput('Finished marking participants in Richard Tippin and John Doe to online!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sets_participants_online_in_random_thread_if_id_not_given()
    {
        Participant::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->count(3)->create();

        $this->artisan('messenger:faker:status')->assertExitCode(0);
    }

    /** @test */
    public function it_sets_participants_to_away()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

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
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:status', [
            'thread' => $group->id,
            '--status' => 'offline',
        ])
            ->expectsOutput('Finished marking participants in First Test Group to offline!')
            ->assertExitCode(0);
    }
}
