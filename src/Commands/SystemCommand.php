<?php

namespace RTippin\MessengerFaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RTippin\MessengerFaker\MessengerFaker;
use Throwable;

class SystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:system 
                                            {thread : ID of the thread you wish to have messaged}
                                            {--type= : Specify system message (INT) type. Random will be chosen if not specified}
                                            {--count=1 : Number of system messages to send}
                                            {--delay=3 : Delay between each system message being sent}
                                            {--admins : Only use admins to send system messages if group thread}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants send system messages.';

    /**
     * Execute the console command.
     *
     * @param MessengerFaker $faker
     * @return void
     * @throws Throwable
     */
    public function handle(MessengerFaker $faker): void
    {
        try {
            $faker->setThreadWithId($this->argument('thread'), $this->option('admins'))
                ->setDelay($this->option('delay'));
        } catch (ModelNotFoundException $e) {
            $this->error('Thread not found.');

            return;
        }

        $this->line('');
        $this->info("Found {$faker->getThreadName()}, now sending system messages...");
        $this->line('');
        $bar = $this->output->createProgressBar($this->option('count'));
        $bar->start();

        for ($x = 1; $x <= $this->option('count'); $x++) {
            $faker->system($this->option('type'), $this->option('count') <= $x);
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        $this->line('');
        $this->info("Finished sending {$this->option('count')} system messages to {$faker->getThreadName()}!");
        $this->line('');
    }
}
