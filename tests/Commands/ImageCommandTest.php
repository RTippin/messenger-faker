<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Facades\Messenger;
use RTippin\MessengerFaker\MessengerFaker;
use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class ImageCommandTest extends MessengerFakerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setBroadcastDriver('null');
        Storage::fake(Messenger::getThreadStorage('disk'));
        app(MessengerFaker::class)->fake();
    }

    /** @test */
    public function it_does_not_find_thread()
    {
        $this->artisan('messenger:faker:image', [
            'thread' => 404,
        ])
            ->expectsOutput('Thread not found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_default_of_1_image_message_to_group()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:image', [
            'thread' => $group->id,
        ])
            ->expectsOutput('Found First Test Group, now messaging images...')
            ->expectsOutput('Using https://source.unsplash.com/random')
            ->expectsOutput('Finished sending 1 image messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_default_of_1_image_message_to_private()
    {
        $private = $this->createPrivateThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:image', [
            'thread' => $private->id,
        ])
            ->expectsOutput('Found Richard Tippin and John Doe, now messaging images...')
            ->expectsOutput('Using https://source.unsplash.com/random')
            ->expectsOutput('Finished sending 1 image messages to Richard Tippin and John Doe!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_image_count()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:image', [
            'thread' => $group->id,
            '--count' => 2,
        ])
            ->expectsOutput('Found First Test Group, now messaging images...')
            ->expectsOutput('Using https://source.unsplash.com/random')
            ->expectsOutput('Finished sending 2 image messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_zero_image_count()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:image', [
            'thread' => $group->id,
            '--count' => 0,
        ])
            ->expectsOutput('Found First Test Group, now messaging images...')
            ->expectsOutput('Using https://source.unsplash.com/random')
            ->expectsOutput('Finished sending 0 image messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_image_url()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:image', [
            'thread' => $group->id,
            '--url' => 'https://example.org/test.png',
        ])
            ->expectsOutput('Found First Test Group, now messaging images...')
            ->expectsOutput('Using https://example.org/test.png')
            ->expectsOutput('Finished sending 1 image messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_local_image_path()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:image', [
            'thread' => $group->id,
            '--local' => true,
        ])
            ->expectsOutput('Found First Test Group, now messaging images...')
            ->expectsOutput('Using a random image from '.config('messenger-faker.paths.images'))
            ->expectsOutput('Finished sending 1 image messages to First Test Group!')
            ->assertExitCode(0);
    }
}
