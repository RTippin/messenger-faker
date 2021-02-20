<?php

namespace RTippin\MessengerFaker\Tests;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\MessengerFaker\MessengerFaker;

class MessengerFakerTest extends MessengerFakerTestCase
{
    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    private MessengerFaker $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->faker = app(MessengerFaker::class);
    }

    /** @test */
    public function faker_sets_messenger_provider()
    {
        $this->faker->setProvider($this->tippin);

        $this->assertTrue(Messenger::isProviderSet());
        $this->assertSame($this->tippin->getKey(), Messenger::getProvider()->getKey());
    }

    /** @test */
    public function faker_throws_model_not_found_when_thread_id_not_found()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->faker->setThreadWithId(404);
    }

    /** @test */
    public function faker_sets_thread_using_id()
    {
        $group = $this->createGroupThread($this->tippin);

        $this->faker->setThreadWithId($group->id);

        $this->assertSame($group->id, $this->faker->getThread()->id);
    }

    /** @test */
    public function faker_sets_thread_using_thread()
    {
        $group = $this->createGroupThread($this->tippin);

        $this->faker->setThread($group);

        $this->assertSame($group, $this->faker->getThread());
    }

    /** @test */
    public function faker_shows_group_thread_name()
    {
        $group = $this->createGroupThread($this->tippin);

        $this->faker->setThread($group);

        $this->assertSame('First Test Group', $this->faker->getThreadName());
    }

    /** @test */
    public function faker_shows_private_thread_names()
    {
        $group = $this->createPrivateThread($this->tippin, $this->doe);

        $this->faker->setThread($group);

        $this->assertSame('Richard Tippin and John Doe', $this->faker->getThreadName());
    }
}
