<?php

namespace RTippin\MessengerFaker\Tests\Commands;

use RTippin\Messenger\Brokers\NullBroadcastBroker;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class DocumentCommandTest extends MessengerFakerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setBroadcastDriver(NullBroadcastBroker::class);
    }

    /** @test */
    public function it_does_not_find_thread()
    {
        $this->artisan('messenger:faker:document', [
            'thread' => 404,
        ])
            ->expectsOutput('Thread not found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_default_of_1_document_message_to_group()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:document', [
            'thread' => $group->id,
        ])
            ->expectsOutput('Found First Test Group, now messaging documents using a random document from '.config('messenger-faker.paths.documents'))
            ->expectsOutput('Finished sending 1 document messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_default_of_1_document_message_to_private()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:document', [
            'thread' => $private->id,
        ])
            ->expectsOutput('Found Richard Tippin and John Doe, now messaging documents using a random document from '.config('messenger-faker.paths.documents'))
            ->expectsOutput('Finished sending 1 document messages to Richard Tippin and John Doe!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sends_document_message_to_random_thread_if_id_not_given()
    {
        Participant::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->count(3)->create();

        $this->artisan('messenger:faker:document')->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_document_count()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:document', [
            'thread' => $group->id,
            '--count' => 2,
        ])
            ->expectsOutput('Found First Test Group, now messaging documents using a random document from '.config('messenger-faker.paths.documents'))
            ->expectsOutput('Finished sending 2 document messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_zero_document_count()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:document', [
            'thread' => $group->id,
            '--count' => 0,
        ])
            ->expectsOutput('Found First Test Group, now messaging documents using a random document from '.config('messenger-faker.paths.documents'))
            ->expectsOutput('Finished sending 0 document messages to First Test Group!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_accepts_document_url()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);

        $this->artisan('messenger:faker:document', [
            'thread' => $group->id,
            '--url' => 'https://example.org/test.pdf',
        ])
            ->expectsOutput('Found First Test Group, now messaging documents using https://example.org/test.pdf')
            ->expectsOutput('Finished sending 1 document messages to First Test Group!')
            ->assertExitCode(0);
    }
}
