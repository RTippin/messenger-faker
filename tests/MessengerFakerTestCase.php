<?php

namespace RTippin\MessengerFaker\Tests;

use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\MessengerServiceProvider;
use RTippin\Messenger\Models\Messenger as MessengerModel;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\Definitions;
use RTippin\MessengerFaker\MessengerFakerServiceProvider;
use RTippin\MessengerFaker\Tests\Fixtures\UserModel;

class MessengerFakerTestCase extends TestCase
{
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
        $tippin = UserModel::create([
            'name' => 'Richard Tippin',
            'email' => 'richard.tippin@gmail.com',
            'password' => 'secret',
        ]);

        $doe = UserModel::create([
            'name' => 'John Doe',
            'email' => 'doe@example.net',
            'password' => 'secret',
        ]);

        MessengerModel::create([
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
        ]);

        MessengerModel::create([
            'owner_id' => $doe->getKey(),
            'owner_type' => get_class($doe),
        ]);
    }

    /**
     * @return MessengerProvider|UserModel
     */
    protected function userTippin()
    {
        return UserModel::where('email', '=', 'richard.tippin@gmail.com')->first();
    }

    /**
     * @return MessengerProvider|UserModel
     */
    protected function userDoe()
    {
        return UserModel::where('email', '=', 'doe@example.net')->first();
    }

    protected function createPrivateThread(MessengerProvider $one, MessengerProvider $two, bool $pending = false): Thread
    {
        $private = Thread::create(Definitions::DefaultThread);

        $private->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => $one->getKey(),
                'owner_type' => get_class($one),
                'pending' => $pending,
            ]));

        $private->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => $two->getKey(),
                'owner_type' => get_class($two),
            ]));

        return $private;
    }

    protected function createGroupThread(MessengerProvider $admin, ...$participants): Thread
    {
        $group = Thread::create([
            'type' => 2,
            'subject' => 'First Test Group',
            'image' => '5.png',
            'add_participants' => true,
            'invitations' => true,
            'calling' => true,
            'knocks' => true,
            'messaging' => true,
            'lockout' => false,
        ]);

        $group->participants()
            ->create(array_merge(Definitions::DefaultAdminParticipant, [
                'owner_id' => $admin->getKey(),
                'owner_type' => get_class($admin),
            ]));

        foreach ($participants as $participant) {
            $group->participants()
                ->create(array_merge(Definitions::DefaultParticipant, [
                    'owner_id' => $participant->getKey(),
                    'owner_type' => get_class($participant),
                ]));
        }

        return $group;
    }
}
