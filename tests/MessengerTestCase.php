<?php

namespace RTippin\MessengerFaker\Tests;

use Orchestra\Testbench\TestCase;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\MessengerServiceProvider;
use RTippin\MessengerFaker\MessengerFakerServiceProvider;
use RTippin\MessengerFaker\Tests\Fixtures\UserModel;
use RTippin\MessengerFaker\Tests\Fixtures\UserModelUuid;

class MessengerTestCase extends TestCase
{
    use HelperTrait;

    /**
     * Set TRUE to run all feature test with
     * provider models/tables using UUIDS.
     */
    const UseUUID = false;

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

        $config->set('messenger.provider_uuids', self::UseUUID);

        $config->set('messenger.providers', $this->getBaseProvidersConfig());
    }

    protected function getBaseProvidersConfig(): array
    {
        return [
            'user' => [
                'model' => $this->getModelUser(),
                'searchable' => true,
                'friendable' => true,
                'devices' => true,
                'default_avatar' => '/path/to/user.png',
                'provider_interactions' => [
                    'can_message' => true,
                    'can_search' => true,
                    'can_friend' => true,
                ],
            ],
        ];
    }

    /**
     * @return MessengerProvider|UserModel|UserModelUuid|string
     */
    protected function getModelUser()
    {
        return self::UseUUID ? UserModelUuid::class : UserModel::class;
    }
}
