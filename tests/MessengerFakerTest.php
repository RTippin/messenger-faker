<?php

namespace RTippin\MessengerFaker\Tests;

use Exception;
use Faker\Generator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ClientEvents\Read;
use RTippin\Messenger\Broadcasting\ClientEvents\Typing;
use RTippin\Messenger\Broadcasting\KnockBroadcast;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Broadcasting\ReactionAddedBroadcast;
use RTippin\Messenger\Events\KnockEvent;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Events\ReactionAddedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\MessengerFaker\MessengerFaker;

class MessengerFakerTest extends MessengerFakerTestCase
{
    /** @test */
    public function it_returns_faker_generator()
    {
        $faker = app(MessengerFaker::class);

        $this->assertInstanceOf(Generator::class, $faker->getFakerGenerator());
    }

    /** @test */
    public function it_sets_messenger_configs()
    {
        app(MessengerFaker::class);

        $this->assertTrue(Messenger::isKnockKnockEnabled());
        $this->assertTrue(Messenger::isMessageReactionsEnabled());
        $this->assertTrue(Messenger::isSystemMessagesEnabled());
        $this->assertSame(0, Messenger::getKnockTimeout());
    }

    /** @test */
    public function it_throws_model_not_found_when_thread_id_not_found()
    {
        $faker = app(MessengerFaker::class);

        $this->expectException(ModelNotFoundException::class);

        $faker->setThreadWithId(404);
    }

    /** @test */
    public function it_throws_exception_when_not_enough_messages_found()
    {
        $faker = app(MessengerFaker::class);
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);
        Message::factory()->for($thread)->owner($this->tippin)->create();
        $faker->setThread($thread);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test does not have 2 or more messages to choose from.');

        $faker->setMessages(2);
    }

    /** @test */
    public function it_sets_thread_using_id()
    {
        $faker = app(MessengerFaker::class);
        $thread = Thread::factory()->group()->create();
        $faker->setThreadWithId($thread->id);

        $this->assertSame($thread->id, $faker->getThread()->id);
    }

    /** @test */
    public function it_sets_random_thread_when_id_null()
    {
        $faker = app(MessengerFaker::class);
        $thread1 = Thread::factory()->group()->create();
        $thread2 = Thread::factory()->group()->create();
        $faker->setThreadWithId();

        $this->assertContains($faker->getThread()->id, [$thread1->id, $thread2->id]);
    }

    /** @test */
    public function it_sets_thread_using_thread()
    {
        $faker = app(MessengerFaker::class);
        $thread = Thread::factory()->group()->create();
        $faker->setThread($thread);

        $this->assertSame($thread, $faker->getThread());
    }

    /** @test */
    public function it_shows_group_thread_name()
    {
        $faker = app(MessengerFaker::class);
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);
        $faker->setThread($thread);

        $this->assertSame('Test', $faker->getThreadName());
    }

    /** @test */
    public function it_shows_private_thread_names()
    {
        $faker = app(MessengerFaker::class);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $faker->setThread($thread);

        $this->assertSame('Richard Tippin and John Doe', $faker->getThreadName());
    }

    /** @test */
    public function it_knocks_at_group_thread()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            KnockBroadcast::class,
            KnockEvent::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group)->knock();

        Event::assertDispatchedTimes(KnockBroadcast::class, 1);
        Event::assertDispatchedTimes(KnockEvent::class, 1);
    }

    /** @test */
    public function it_knocks_at_private_thread()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            KnockBroadcast::class,
            KnockEvent::class,
        ]);
        $faker = app(MessengerFaker::class);
        $private = $this->createPrivateThread($this->tippin, $this->doe);
        $faker->setThread($private)->knock();

        Event::assertDispatchedTimes(KnockBroadcast::class, 2);
        Event::assertDispatchedTimes(KnockEvent::class, 2);
    }

    /** @test */
    public function it_marks_read_does_nothing_when_no_last_message()
    {
        Event::fake([
            Read::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin);
        $faker->setThread($group)->read();

        Event::assertNotDispatched(Read::class);
    }

    /** @test */
    public function it_marks_read_for_all_participants()
    {
        Event::fake([
            Read::class,
        ]);
        $read = now()->addMinute();
        Carbon::setTestNow($read);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $this->createMessage($group, $this->tippin);
        $faker->setThread($group)->read();

        Event::assertDispatchedTimes(Read::class, 2);
        $this->assertSame(2, Participant::whereNotNull('last_read')->count());
    }

    /** @test */
    public function it_mark_read_for_admin_participants()
    {
        Event::fake([
            Read::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $this->createMessage($group, $this->tippin);
        $faker->setThread($group, true)->read();

        Event::assertDispatchedTimes(Read::class, 1);
        $this->assertSame(1, Participant::whereNotNull('last_read')->count());
    }

    /** @test */
    public function it_mark_unread_for_all_participants()
    {
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        DB::table('participants')->update([
            'last_read' => now(),
        ]);
        $faker->setThread($group)->unread();

        $this->assertSame(2, Participant::whereNull('last_read')->count());
    }

    /** @test */
    public function it_mark_unread_for_admin_participants()
    {
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        DB::table('participants')->update([
            'last_read' => now(),
        ]);
        $faker->setThread($group, true)->unread();

        $this->assertSame(1, Participant::whereNull('last_read')->count());
    }

    /** @test */
    public function it_makes_all_participants_type()
    {
        Event::fake([
            Typing::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group)->typing();

        Event::assertDispatchedTimes(Typing::class, 2);
    }

    /** @test */
    public function it_makes_admin_participants_type()
    {
        Event::fake([
            Typing::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group, true)->typing();

        Event::assertDispatchedTimes(Typing::class, 1);
    }

    /** @test */
    public function it_messages_using_random_participant_and_calls_typing()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group)->message();

        $this->assertDatabaseCount('messages', 1);
        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
    }

    /** @test */
    public function it_messages_and_marks_used_participants_read_when_final()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
            Read::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group)->message()->message()->message()->message(true);

        $this->assertDatabaseCount('messages', 4);
        Event::assertDispatchedTimes(NewMessageBroadcast::class, 4);
        Event::assertDispatchedTimes(NewMessageEvent::class, 4);
        Event::assertDispatchedTimes(Typing::class, 4);
        Event::assertDispatched(Read::class);
    }

    /** @test */
    public function it_seeds_image_messages()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);
        Storage::fake(Messenger::getThreadStorage('disk'));
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group)->image();

        $this->assertDatabaseHas('messages', [
            'type' => 1,
        ]);
        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
    }

    /** @test */
    public function it_seeds_document_messages()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);
        Storage::fake(Messenger::getThreadStorage('disk'));
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group)->document();

        $this->assertDatabaseHas('messages', [
            'type' => 2,
        ]);
        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
    }

    /** @test */
    public function it_seeds_audio_messages()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);
        Storage::fake(Messenger::getThreadStorage('disk'));
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group)->audio();

        $this->assertDatabaseHas('messages', [
            'type' => 3,
        ]);
        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
    }

    /** @test */
    public function it_seeds_reactions()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ReactionAddedBroadcast::class,
            ReactionAddedEvent::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $this->createMessage($group, $this->tippin);
        $faker->setThread($group)->setMessages(1)->reaction();

        $this->assertDatabaseCount('message_reactions', 1);
        Event::assertDispatched(ReactionAddedBroadcast::class);
        Event::assertDispatched(ReactionAddedEvent::class);
    }

    /**
     * @test
     * @dataProvider systemMessageTypes
     * @param $type
     */
    public function it_seeds_system_messages($type)
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group)->system($type);

        $this->assertDatabaseHas('messages', [
            'type' => $type,
        ]);
        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
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
            'BOT_ADDED' => [100],
            'BOT_RENAMED' => [101],
            'BOT_AVATAR_CHANGED' => [102],
            'BOT_REMOVED' => [103],
        ];
    }
}
