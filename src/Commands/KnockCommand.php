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
    protected $signature = 'messenger:faker:knock
                                            {thread : ID of the thread you wish to knock at} 
                                            {--delay=1 : delay between rounds}
                                            {--rounds=1 : Number of loops to run the knock}';

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
            $knock->setThread($this->argument('thread'));
        } catch (ModelNotFoundException $e) {
            $this->error('Thread not found.');

            return;
        }

        $this->info("Thread: {$knock->getThreadName()} found, knocking!");

        $bar = $this->output->createProgressBar($this->option('rounds'));

        $bar->start();

        for ($x = 1; $x <= $this->option('rounds'); $x++) {
            $knock->execute();

            $bar->advance();

            if ($this->option('rounds') > $x) {
                sleep($this->option('delay'));
            }
        }

        $bar->finish();

        $this->info(' (done)');

        $this->info(" Finished knocking at {$knock->getThreadName()}!");
    }
}
