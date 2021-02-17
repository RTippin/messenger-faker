<?php

namespace RTippin\MessengerFaker;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use RTippin\MessengerFaker\Commands\KnockCommand;
use RTippin\MessengerFaker\Commands\ReadCommand;

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
                ReadCommand::class,
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
            ReadCommand::class,
        ];
    }
}
