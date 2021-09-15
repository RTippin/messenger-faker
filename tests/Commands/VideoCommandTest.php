<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use RTippin\Messenger\Brokers\NullBroadcastBroker;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class VideoCommandTest extends MessengerFakerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setBroadcastDriver(NullBroadcastBroker::class);
    }

    /** @test */
    public function it_does_not_find_thread()
    {
        $this->artisan('messenger:faker:video', [
            'thread' => 404,
        ])
            ->expectsOutput('Thread not found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_default_of_1_video_message_to_group()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:video', [
            'thread' => $group->id,
        ])
            ->expectsOutput('Found First Test Group, now messaging videos using a random video file from '.config('messenger-faker.paths.videos'))
            ->expectsOutput('Finished sending 1 video messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_default_of_1_video_message_to_private()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:video', [
            'thread' => $private->id,
        ])
            ->expectsOutput('Found Richard Tippin and John Doe, now messaging videos using a random video file from '.config('messenger-faker.paths.videos'))
            ->expectsOutput('Finished sending 1 video messages to Richard Tippin and John Doe!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_video_message_to_random_thread_if_id_not_given()
    {
        Participant::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->count(3)->create();

        $this->artisan('messenger:faker:video')->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_video_count()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:video', [
            'thread' => $group->id,
            '--count' => 2,
        ])
            ->expectsOutput('Found First Test Group, now messaging videos using a random video file from '.config('messenger-faker.paths.videos'))
            ->expectsOutput('Finished sending 2 video messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_zero_video_count()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:video', [
            'thread' => $group->id,
            '--count' => 0,
        ])
            ->expectsOutput('Found First Test Group, now messaging videos using a random video file from '.config('messenger-faker.paths.videos'))
            ->expectsOutput('Finished sending 0 video messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_video_url()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:video', [
            'thread' => $group->id,
            '--url' => 'https://example.org/test.mov',
        ])
            ->expectsOutput('Found First Test Group, now messaging videos using https://example.org/test.mov')
            ->expectsOutput('Finished sending 1 video messages to First Test Group!')
            ->assertExitCode(0);
    }
}
