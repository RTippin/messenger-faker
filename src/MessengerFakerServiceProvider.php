<?php

namespace RTippin\MessengerFaker;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class MessengerFakerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(
            MessengerFaker::class,
            MessengerFaker::class
        );
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {

            $this->commands([]);
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
            MessengerFaker::class,
        ];
    }
}
