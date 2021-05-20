<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class AudioCommandTest extends MessengerFakerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setBroadcastDriver('null');
    }

    /** @test */
    public function it_does_not_find_thread()
    {
        $this->artisan('messenger:faker:audio', [
            'thread' => 404,
        ])
            ->expectsOutput('Thread not found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_default_of_1_audio_message_to_group()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:audio', [
            'thread' => $group->id,
        ])
            ->expectsOutput('Found First Test Group, now messaging audio...')
            ->expectsOutput('Using a random audio file from '.config('messenger-faker.paths.audio'))
            ->expectsOutput('Finished sending 1 audio messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_default_of_1_audio_message_to_private()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:audio', [
            'thread' => $private->id,
        ])
            ->expectsOutput('Found Richard Tippin and John Doe, now messaging audio...')
            ->expectsOutput('Using a random audio file from '.config('messenger-faker.paths.audio'))
            ->expectsOutput('Finished sending 1 audio messages to Richard Tippin and John Doe!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_audio_message_to_random_thread_if_id_not_given()
    {
        Participant::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->count(3)->create();

        $this->artisan('messenger:faker:audio')->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_audio_count()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:audio', [
            'thread' => $group->id,
            '--count' => 2,
        ])
            ->expectsOutput('Found First Test Group, now messaging audio...')
            ->expectsOutput('Using a random audio file from '.config('messenger-faker.paths.audio'))
            ->expectsOutput('Finished sending 2 audio messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_zero_audio_count()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:audio', [
            'thread' => $group->id,
            '--count' => 0,
        ])
            ->expectsOutput('Found First Test Group, now messaging audio...')
            ->expectsOutput('Using a random audio file from '.config('messenger-faker.paths.audio'))
            ->expectsOutput('Finished sending 0 audio messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_audio_url()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:audio', [
            'thread' => $group->id,
            '--url' => 'https://example.org/test.mp3',
        ])
            ->expectsOutput('Found First Test Group, now messaging audio...')
            ->expectsOutput('Using https://example.org/test.mp3')
            ->expectsOutput('Finished sending 1 audio messages to First Test Group!')
            ->assertExitCode(0);
    }
}
