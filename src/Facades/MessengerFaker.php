<?php

namespace RTippin\MessengerFaker\Facades;

use Illuminate\Support\Facades\Facade;

class MessengerFaker extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \RTippin\MessengerFaker\MessengerFaker::class;
    }
}
