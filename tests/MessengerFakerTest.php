<?php

namespace RTippin\MessengerFaker\Tests;

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
}
