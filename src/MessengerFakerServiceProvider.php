<?php

namespace RTippin\MessengerFaker;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use RTippin\MessengerFaker\Commands\AudioCommand;
use RTippin\MessengerFaker\Commands\DocumentCommand;
use RTippin\MessengerFaker\Commands\ImageCommand;
use RTippin\MessengerFaker\Commands\KnockCommand;
use RTippin\MessengerFaker\Commands\MessageCommand;
use RTippin\MessengerFaker\Commands\RandomCommand;
use RTippin\MessengerFaker\Commands\ReactCommand;
use RTippin\MessengerFaker\Commands\ReadCommand;
use RTippin\MessengerFaker\Commands\SystemCommand;
use RTippin\MessengerFaker\Commands\TypingCommand;
use RTippin\MessengerFaker\Commands\UnReadCommand;

class MessengerFakerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/messenger-faker.php', 'messenger-faker');

        $this->app->singleton(MessengerFaker::class, MessengerFaker::class);
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/messenger-faker.php' => config_path('messenger-faker.php'),
            ], 'messenger-faker');

            $this->commands([
                AudioCommand::class,
                DocumentCommand::class,
                ImageCommand::class,
                KnockCommand::class,
                MessageCommand::class,
                RandomCommand::class,
                ReactCommand::class,
                ReadCommand::class,
                SystemCommand::class,
                TypingCommand::class,
                UnReadCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            AudioCommand::class,
            DocumentCommand::class,
            ImageCommand::class,
            KnockCommand::class,
            MessageCommand::class,
            MessengerFaker::class,
            RandomCommand::class,
            ReactCommand::class,
            ReadCommand::class,
            SystemCommand::class,
            TypingCommand::class,
            UnReadCommand::class,
        ];
    }
}
