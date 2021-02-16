<?php

use RTippin\MessengerFaker\MessengerFaker;

if (! function_exists('messengerFaker')) {
    /**
     * @return MessengerFaker
     *
     * Return the active instance of the messengerFaker system
     */
    function messengerFaker(): MessengerFaker
    {
        return resolve(MessengerFaker::class);
    }
}
