<?php

namespace RTippin\MessengerFaker\Tests\Bots;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ClientEvents\Typing;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\MessengerFaker\Bots\FakerBot;
use RTippin\MessengerFaker\Tests\MessengerFakerTestCase;

class FakerBotTest extends MessengerFakerTestCase
{
    /** @test */
    public function it_is_registered_from_our_service_provider()
    {
        $this->assertTrue(MessengerBots::isValidHandler(FakerBot::class));
    }

    /** @test */
    public function it_returns_formatted_settings()
    {
        $expected = [
            'alias' => 'faker',
            'description' => 'Access our underlying messenger faker commands. Eg: [ !faker {action} {count?} {delay?}]',
            'name' => 'Messenger Faker Commands',
            'unique' => true,
            'authorize' => false,
            'triggers' => ['!faker'],
            'match' => \RTippin\Messenger\MessengerBots::MATCH_STARTS_WITH_CASELESS,
        ];

        $this->assertSame($expected, MessengerBots::getHandlers(FakerBot::class)->toArray());
    }

    /** @test */
    public function it_can_be_attached_to_a_bot_handler()
    {
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'faker',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function it_doesnt_find_valid_command()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!faker unknown')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $faker = MessengerBots::initializeHandler(FakerBot::class)
            ->setDataForHandler($thread, $action, $message, '!faker');

        $faker->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Please select a valid choice, eg: ( !faker {action} {count?} {delay?} )',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'Available actions: [audio, document, image, knock, message, random, react, system, typing, video]',
        ]);
        $this->assertTrue($faker->shouldReleaseCooldown());
    }

    /** @test */
    public function it_uses_default_count_and_delay()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!faker message')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $faker = MessengerBots::initializeHandler(FakerBot::class)
            ->setDataForHandler($thread, $action, $message, '!faker');

        $faker->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Faker initiating. Sending 5 message actions with a 1 second delay.',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'Faker actions completed!',
        ]);
        $this->assertFalse($faker->shouldReleaseCooldown());
    }

    /** @test */
    public function it_accepts_count_and_delay()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!faker message 25 5')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $faker = MessengerBots::initializeHandler(FakerBot::class)
            ->setDataForHandler($thread, $action, $message, '!faker');

        $faker->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Faker initiating. Sending 25 message actions with a 5 second delay.',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'Faker actions completed!',
        ]);
        $this->assertFalse($faker->shouldReleaseCooldown());
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!faker message')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);

        MessengerBots::initializeHandler(FakerBot::class)
            ->setDataForHandler($thread, $action, $message, '!faker')
            ->handle();

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
    }
}
