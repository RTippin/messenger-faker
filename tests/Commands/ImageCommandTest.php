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
            '--delay' => 0,
        ])
            ->expectsOutput('Found First Test Group, now messaging images...')
            ->expectsOutput('Finished sending 1 image messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_default_of_1_image_message_to_private()
    {
        $private = $this->createPrivateThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:image', [
            'thread' => $private->id,
            '--delay' => 0,
        ])
            ->expectsOutput('Found Richard Tippin and John Doe, now messaging images...')
            ->expectsOutput('Finished sending 1 image messages to Richard Tippin and John Doe!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_message_count()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:image', [
            'thread' => $group->id,
            '--count' => 2,
            '--delay' => 0,
        ])
            ->expectsOutput('Found First Test Group, now messaging images...')
            ->expectsOutput('Finished sending 2 image messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_zero_message_count()
    {
        $group = $this->createGroupThread($this->userTippin(), $this->userDoe());

        $this->artisan('messenger:faker:image', [
            'thread' => $group->id,
            '--count' => 0,
            '--delay' => 0,
        ])
            ->expectsOutput('Found First Test Group, now messaging images...')
            ->expectsOutput('Finished sending 0 image messages to First Test Group!')
            ->assertExitCode(0);
    }
}
