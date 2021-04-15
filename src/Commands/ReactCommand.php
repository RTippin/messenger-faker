<?php

namespace RTippin\MessengerFaker\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RTippin\MessengerFaker\MessengerFaker;
use Throwable;

class ReactCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:react 
                                            {thread : ID of the thread you wish to have reactions added to messages}
                                            {--count=5 : Number of reactions to add}
                                            {--messages=5 : Number of latest messages to choose from}
                                            {--delay=1 : Delay between each reaction}
                                            {--admins : Only use admins to send audio messages if group thread}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants add reactions to the latest messages selected.';

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
                ->setMessages($this->option('messages'))
                ->setDelay($this->option('delay'));
        } catch (ModelNotFoundException $e) {
            $this->error('Thread not found.');

            return;
        } catch (Exception $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->line('');
        $this->info("Found {$faker->getThreadName()}, now adding reactions to the {$this->option('messages')} most recent messages...");
        $this->line('');
        $bar = $this->output->createProgressBar($this->option('count'));
        $bar->start();

        for ($x = 1; $x <= $this->option('count'); $x++) {
            $faker->reaction($this->option('count') <= $x);
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        $this->line('');
        $this->info("Finished sending {$this->option('count')} reactions to {$faker->getThreadName()}!");
        $this->line('');
    }
}
