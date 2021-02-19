<?php

namespace RTippin\MessengerFaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Exceptions\KnockException;
use RTippin\MessengerFaker\MessengerFaker;

class KnockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:knock 
                                            {thread : ID of the thread you wish to knock at}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a knock to the given thread.';


    /**
     * Execute the console command.
     *
     * @param MessengerFaker $faker
     * @throws FeatureDisabledException
     * @throws InvalidArgumentException
     * @throws InvalidProviderException
     * @throws KnockException
     */
    public function handle(MessengerFaker $faker): void
    {
        try {
            $faker->setThreadWithId($this->argument('thread'));
        } catch (ModelNotFoundException $e) {
            $this->error('Thread not found.');

            return;
        }

        $faker->knock();

        $this->info("Finished knocking at {$faker->getThreadName()}!");
    }
}
