<?php

namespace RTippin\MessengerFaker\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RTippin\MessengerFaker\MessengerFaker;
use Throwable;

class AudioCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:audio 
                                            {thread? : ID of the thread you want to seed. Random if not set}
                                            {--count=1 : Number of audio messages to send}
                                            {--delay=3 : Delay between each audio message being sent}
                                            {--admins : Only use admins to send audio messages if group thread}
                                            {--url= : Set the path/URL we grab a audio from. Default uses local storage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants send audio messages. Will also emit typing and mark read.';

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

        if (! is_null($this->option('url'))) {
            $message = $this->option('url');
        } else {
            $message = 'a random audio file from '.config('messenger-faker.paths.audio');
        }
        $this->line('');
        $this->info("Found {$faker->getThreadName()}, now messaging audio...");
        $this->info("Using {$message}");
        $this->line('');
        $bar = $this->output->createProgressBar($this->option('count'));
        $bar->start();

        try {
            for ($x = 1; $x <= $this->option('count'); $x++) {
                $faker->audio($this->option('count') <= $x, $this->option('url'));
                $bar->advance();
            }
        } catch (Exception $e) {
            $this->line('');
            $this->line('');
            $this->error($e->getMessage());

            return;
        }

        $bar->finish();
        $this->line('');
        $this->line('');
        $this->info("Finished sending {$this->option('count')} audio messages to {$faker->getThreadName()}!");
        $this->line('');
    }
}
