<?php

namespace RTippin\MessengerFaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Exceptions\KnockException;
use RTippin\MessengerFaker\Faker\Knock;

class KnockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:knock {thread : ID of the thread you wish to knock at}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a knock to the given thread.';

    /**
     * Execute the console command.
     *
     * @param Knock $knock
     * @return void
     * @throws InvalidProviderException|InvalidArgumentException|FeatureDisabledException|KnockException
     */
    public function handle(Knock $knock): void
    {
        try {
            $knock->setThreadWithId($this->argument('thread'));
        } catch (ModelNotFoundException $e) {
            $this->error('Thread not found.');

            return;
        }

        $knock->execute();

        $this->info("Finished knocking at {$knock->getThreadName()}!");
    }
}
