<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use RTippin\Messenger\Facades\Messenger;
use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class SystemCommandTest extends MessengerFakerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setBroadcastDriver('null');
    }

    /** @test */
    public function it_does_not_find_thread()
    {
        $this->artisan('messenger:faker:system', [
            'thread' => 404,
        ])
            ->expectsOutput('Thread not found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_does_not_allow_invalid_type_on_private()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:system', [
            'thread' => $private->id,
            '--type' => 99,
        ])
            ->expectsOutput('Found Richard Tippin and John Doe, now sending system messages...')
            ->expectsOutput('Invalid system message type for private thread.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_does_not_allow_invalid_type_on_group()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:system', [
            'thread' => $group->id,
            '--type' => 404,
        ])
            ->expectsOutput('Found First Test Group, now sending system messages...')
            ->expectsOutput('Invalid system message type.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_default_of_1_system_message_to_group()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:system', [
            'thread' => $group->id,
        ])
            ->expectsOutput('Found First Test Group, now sending system messages...')
            ->expectsOutput('Finished sending 1 system messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_default_of_1_system_message_to_private()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:system', [
            'thread' => $private->id,
        ])
            ->expectsOutput('Found Richard Tippin and John Doe, now sending system messages...')
            ->expectsOutput('Finished sending 1 system messages to Richard Tippin and John Doe!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_message_count()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:system', [
            'thread' => $group->id,
            '--count' => 2,
        ])
            ->expectsOutput('Found First Test Group, now sending system messages...')
            ->expectsOutput('Finished sending 2 system messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_zero_message_count()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:system', [
            'thread' => $group->id,
            '--count' => 0,
        ])
            ->expectsOutput('Found First Test Group, now sending system messages...')
            ->expectsOutput('Finished sending 0 system messages to First Test Group!')
            ->assertExitCode(0);
    }

    /**
     * @test
     * @dataProvider systemMessageTypes
     * @param $type
     */
    public function it_accepts_type($type)
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:system', [
            'thread' => $group->id,
            '--type' => $type,
        ])
            ->expectsOutput('Found First Test Group, now sending system messages...')
            ->expectsOutput('Finished sending 1 system messages to First Test Group!')
            ->assertExitCode(0);
    }

    /**
     * @test
     * @dataProvider notEnoughParticipants
     * @param $type
     */
    public function it_doesnt_have_enough_participants($type)
    {
        $group = $this->createGroupThread($this->tippin);

        $this->artisan('messenger:faker:system', [
            'thread' => $group->id,
            '--type' => $type,
        ])
            ->expectsOutput('Found First Test Group, now sending system messages...')
            ->expectsOutput('No other participants to choose from.')
            ->assertExitCode(0);
    }

    public function systemMessageTypes(): array
    {
        return [
            'PARTICIPANT_JOINED_WITH_INVITE' => [88],
            'VIDEO_CALL' => [90],
            'GROUP_AVATAR_CHANGED' => [91],
            'THREAD_ARCHIVED' => [92],
            'GROUP_CREATED' => [93],
            'GROUP_RENAMED' => [94],
            'DEMOTED_ADMIN' => [95],
            'PROMOTED_ADMIN' => [96],
            'PARTICIPANT_LEFT_GROUP' => [97],
            'PARTICIPANT_REMOVED' => [98],
            'PARTICIPANTS_ADDED' => [99],
        ];
    }

    public function notEnoughParticipants(): array
    {
        return [
            'DEMOTED_ADMIN' => [95],
            'PROMOTED_ADMIN' => [96],
            'PARTICIPANT_REMOVED' => [98],
            'PARTICIPANTS_ADDED' => [99],
        ];
    }
}
