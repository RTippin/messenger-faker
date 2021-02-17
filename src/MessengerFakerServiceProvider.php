<?php

namespace RTippin\MessengerFaker;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use RTippin\MessengerFaker\Commands\KnockCommand;
use RTippin\MessengerFaker\Commands\OnlineStatusCommand;
use RTippin\MessengerFaker\Commands\ReadCommand;
use RTippin\MessengerFaker\Commands\TypingCommand;

class MessengerFakerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                KnockCommand::class,
                OnlineStatusCommand::class,
                ReadCommand::class,
                TypingCommand::class,
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
            KnockCommand::class,
            OnlineStatusCommand::class,
            ReadCommand::class,
            TypingCommand::class,
        ];
    }
}
