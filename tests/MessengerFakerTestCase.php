<?php

namespace RTippin\MessengerFaker\Tests;

use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\MessengerServiceProvider;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Messenger as MessengerModel;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
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
        $config->set('messenger.providers', $this->getBaseProvidersConfig());
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
        $this->storeBaseUsers();
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    protected function getBaseProvidersConfig(): array
    {
        return [
            'user' => [
                'model' => UserModel::class,
                'searchable' => false,
                'friendable' => false,
                'devices' => false,
                'default_avatar' => '/path/to/user.png',
                'provider_interactions' => [
                    'can_message' => true,
                    'can_search' => true,
                    'can_friend' => true,
                ],
            ],
        ];
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

    /**
     * @return MessengerProvider|UserModel
     */
    protected function userTippin()
    {
        return UserModel::where('email', '=', 'tippindev@gmail.com')->first();
    }

    /**
     * @return MessengerProvider|UserModel
     */
    protected function userDoe()
    {
        return UserModel::where('email', '=', 'doe@example.net')->first();
    }

    protected function createPrivateThread($one, $two, bool $pending = false): Thread
    {
        $private = Thread::factory()->create();
        Participant::factory()
            ->for($private)
            ->owner($one)
            ->create([
                'pending' => $pending,
            ]);
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
