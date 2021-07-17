<?php

namespace RTippin\MessengerFaker\Tests;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\MessengerServiceProvider;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Messenger as MessengerModel;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\MessengerFaker\MessengerFaker;
use RTippin\MessengerFaker\MessengerFakerServiceProvider;
use RTippin\MessengerFaker\Tests\Fixtures\UserModel;

class MessengerFakerTestCase extends TestCase
{
    /**
     * @var MessengerProvider
     */
    protected $tippin;

    /**
     * @var MessengerProvider
     */
    protected $doe;

    protected function getPackageProviders($app): array
    {
        return [
            MessengerServiceProvider::class,
            MessengerFakerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $config = $app->get('config');

        $config->set('messenger.provider_uuids', false);
        $config->set('messenger.storage.threads.disk', 'messenger');
        $config->set('database.default', 'testbench');
        $config->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/Fixtures/migrations');
        $this->artisan('migrate', [
            '--database' => 'testbench',
        ])->run();
        Messenger::registerProviders([
            UserModel::class,
        ]);
        $this->storeBaseUsers();
        Storage::fake('messenger');
        MessengerFaker::testing();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        BaseMessengerAction::enableEvents();

        parent::tearDown();
    }

    private function storeBaseUsers(): void
    {
        $this->tippin = UserModel::create([
            'name' => 'Richard Tippin',
            'email' => 'tippindev@gmail.com',
            'password' => 'secret',
        ]);
        $this->doe = UserModel::create([
            'name' => 'John Doe',
            'email' => 'doe@example.net',
            'password' => 'secret',
        ]);
        MessengerModel::factory()->owner($this->tippin)->create();
        MessengerModel::factory()->owner($this->doe)->create();
    }

    protected function createPrivateThread($one, $two): Thread
    {
        $private = Thread::factory()->create();
        Participant::factory()
            ->for($private)
            ->owner($one)
            ->create();
        Participant::factory()
            ->for($private)
            ->owner($two)
            ->create();

        return $private;
    }

    protected function createGroupThread($admin, ...$participants): Thread
    {
        $group = Thread::factory()
            ->group()
            ->create([
                'subject' => 'First Test Group',
                'image' => '5.png',
            ]);
        Participant::factory()
            ->for($group)
            ->owner($admin)
            ->admin()
            ->create();

        foreach ($participants as $participant) {
            Participant::factory()
                ->for($group)
                ->owner($participant)
                ->create();
        }

        return $group;
    }

    protected function createMessage($thread, $owner): Message
    {
        return Message::factory()
            ->for($thread)
            ->owner($owner)
            ->create([
                'body' => 'First Test Message',
            ]);
    }
}
