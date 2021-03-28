<?php

namespace RTippin\MessengerFaker\Tests;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Broadcasting\KnockBroadcast;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\KnockEvent;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\MessengerFaker\Broadcasting\OnlineStatusBroadcast;
use RTippin\MessengerFaker\Broadcasting\ReadBroadcast;
use RTippin\MessengerFaker\Broadcasting\TypingBroadcast;
use RTippin\MessengerFaker\MessengerFaker;

class MessengerFakerTest extends MessengerFakerTestCase
{
    private MessengerProvider $tippin;
    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->doe = $this->userDoe();
    }

    /** @test */
    public function it_sets_messenger_configs()
    {
        app(MessengerFaker::class);

        $this->assertTrue(Messenger::isKnockKnockEnabled());
        $this->assertTrue(Messenger::isOnlineStatusEnabled());
        $this->assertSame(0, Messenger::getKnockTimeout());
        $this->assertSame(1, Messenger::getOnlineCacheLifetime());
    }

    /** @test */
    public function it_throws_model_not_found_when_thread_id_not_found()
    {
        $faker = app(MessengerFaker::class);

        $this->expectException(ModelNotFoundException::class);

        $faker->setThreadWithId(404);
    }

    /** @test */
    public function it_sets_thread_using_id()
    {
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin);
        $faker->setThreadWithId($group->id);

        $this->assertSame($group->id, $faker->getThread()->id);
    }

    /** @test */
    public function it_sets_thread_using_thread()
    {
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin);
        $faker->setThread($group);

        $this->assertSame($group, $faker->getThread());
    }

    /** @test */
    public function it_shows_group_thread_name()
    {
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin);
        $faker->setThread($group);

        $this->assertSame('First Test Group', $faker->getThreadName());
    }

    /** @test */
    public function it_shows_private_thread_names()
    {
        $faker = app(MessengerFaker::class);
        $group = $this->createPrivateThread($this->tippin, $this->doe);
        $faker->setThread($group);

        $this->assertSame('Richard Tippin and John Doe', $faker->getThreadName());
    }

    /** @test */
    public function it_knocks_at_group_thread()
    {
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
    public function it_sets_online_status_for_all_participants()
    {
        Event::fake([
            OnlineStatusBroadcast::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group)->status('online');

        Event::assertDispatchedTimes(OnlineStatusBroadcast::class, 2);
        $this->assertSame('online', Cache::get("user:online:{$this->tippin->getKey()}"));
        $this->assertSame('online', Cache::get("user:online:{$this->doe->getKey()}"));
    }

    /** @test */
    public function it_sets_away_status_for_all_participants()
    {
        Event::fake([
            OnlineStatusBroadcast::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group)->status('away');

        Event::assertDispatchedTimes(OnlineStatusBroadcast::class, 2);
        $this->assertSame('away', Cache::get("user:online:{$this->tippin->getKey()}"));
        $this->assertSame('away', Cache::get("user:online:{$this->doe->getKey()}"));
    }

    /** @test */
    public function it_sets_offline_status_for_all_participants()
    {
        Event::fake([
            OnlineStatusBroadcast::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group)->status('offline');

        Event::assertDispatchedTimes(OnlineStatusBroadcast::class, 2);
        $this->assertFalse(Cache::has("user:online:{$this->tippin->getKey()}"));
        $this->assertFalse(Cache::has("user:online:{$this->doe->getKey()}"));
    }

    /** @test */
    public function it_sets_online_status_for_admin_participants()
    {
        Event::fake([
            OnlineStatusBroadcast::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group, true)->status('online');

        Event::assertDispatchedTimes(OnlineStatusBroadcast::class, 1);
        $this->assertSame('online', Cache::get("user:online:{$this->tippin->getKey()}"));
        $this->assertFalse(Cache::has("user:online:{$this->doe->getKey()}"));
    }

    /** @test */
    public function it_mark_read_does_nothing_when_no_last_message()
    {
        Event::fake([
            ReadBroadcast::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin);
        $faker->setThread($group)->read();

        Event::assertNotDispatched(ReadBroadcast::class);
    }

    /** @test */
    public function it_mark_read_for_all_participants()
    {
        Event::fake([
            ReadBroadcast::class,
        ]);
        $read = now()->addMinute();
        Carbon::setTestNow($read);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $this->createMessage($group, $this->tippin);
        $faker->setThread($group)->read();

        Event::assertDispatchedTimes(ReadBroadcast::class, 2);
        $this->assertSame(2, Participant::whereNotNull('last_read')->count());
    }

    /** @test */
    public function it_mark_read_for_admin_participants()
    {
        Event::fake([
            ReadBroadcast::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $this->createMessage($group, $this->tippin);
        $faker->setThread($group, true)->read();

        Event::assertDispatchedTimes(ReadBroadcast::class, 1);
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
    public function it_makes_all_participants_type_and_sends_online_status()
    {
        Event::fake([
            OnlineStatusBroadcast::class,
            TypingBroadcast::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group)->typing();

        Event::assertDispatchedTimes(OnlineStatusBroadcast::class, 2);
        Event::assertDispatchedTimes(TypingBroadcast::class, 2);
    }

    /** @test */
    public function it_makes_admin_participants_type_and_sends_online_status()
    {
        Event::fake([
            OnlineStatusBroadcast::class,
            TypingBroadcast::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group, true)->typing();

        Event::assertDispatchedTimes(OnlineStatusBroadcast::class, 1);
        Event::assertDispatchedTimes(TypingBroadcast::class, 1);
    }

    /** @test */
    public function it_messages_using_random_participant_and_calls_typing()
    {
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            OnlineStatusBroadcast::class,
            TypingBroadcast::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group)->message();

        $this->assertDatabaseCount('messages', 1);
        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(OnlineStatusBroadcast::class);
        Event::assertDispatched(TypingBroadcast::class);
    }

    /** @test */
    public function it_messages_and_marks_used_participants_read_when_final()
    {
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            OnlineStatusBroadcast::class,
            TypingBroadcast::class,
            ReadBroadcast::class,
        ]);
        $faker = app(MessengerFaker::class);
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group)->message()->message(true);

        $this->assertDatabaseCount('messages', 2);
        Event::assertDispatchedTimes(NewMessageBroadcast::class, 2);
        Event::assertDispatchedTimes(NewMessageEvent::class, 2);
        Event::assertDispatchedTimes(OnlineStatusBroadcast::class, 2);
        Event::assertDispatchedTimes(TypingBroadcast::class, 2);
        Event::assertDispatched(ReadBroadcast::class);
    }

    /** @test */
    public function it_seeds_image_messages()
    {
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            OnlineStatusBroadcast::class,
            TypingBroadcast::class,
        ]);
        Storage::fake(Messenger::getThreadStorage('disk'));
        $faker = app(MessengerFaker::class)->fake();
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group)->image();

        $this->assertDatabaseHas('messages', [
            'type' => 1,
        ]);
        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(OnlineStatusBroadcast::class);
        Event::assertDispatched(TypingBroadcast::class);
    }

    /** @test */
    public function it_seeds_document_messages()
    {
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            OnlineStatusBroadcast::class,
            TypingBroadcast::class,
        ]);
        Storage::fake(Messenger::getThreadStorage('disk'));
        $faker = app(MessengerFaker::class)->fake();
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group)->document();

        $this->assertDatabaseHas('messages', [
            'type' => 2,
        ]);
        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(OnlineStatusBroadcast::class);
        Event::assertDispatched(TypingBroadcast::class);
    }

    /** @test */
    public function it_seeds_audio_messages()
    {
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            OnlineStatusBroadcast::class,
            TypingBroadcast::class,
        ]);
        Storage::fake(Messenger::getThreadStorage('disk'));
        $faker = app(MessengerFaker::class)->fake();
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $faker->setThread($group)->audio();

        $this->assertDatabaseHas('messages', [
            'type' => 3,
        ]);
        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(OnlineStatusBroadcast::class);
        Event::assertDispatched(TypingBroadcast::class);
    }

    /**
     * @test
     * @dataProvider systemMessageTypes
     * @param $type
     */
    public function it_seeds_system_messages($type)
    {
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
        ];
    }
}
