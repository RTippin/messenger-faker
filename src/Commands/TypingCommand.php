<?php

namespace RTippin\MessengerFaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RTippin\MessengerFaker\MessengerFaker;

class TypingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:typing 
                                            {thread : ID of the thread you wish to have typing}
                                            {--admins : Only use admins for typing if group thread}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants type.';

    /**
     * Execute the console command.
     *
     * @param MessengerFaker $faker
     * @return void
     */
    public function handle(MessengerFaker $faker): void
    {
        try {
            $faker->setThreadWithId($this->argument('thread'), $this->option('admins'));
        } catch (ModelNotFoundException $e) {
            $this->error('Thread not found.');

            return;
        }

        $faker->typing();

        $this->info("Finished making participants in {$faker->getThreadName()} type!");
    }
}
