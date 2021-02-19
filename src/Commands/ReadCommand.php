<?php

namespace RTippin\MessengerFaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RTippin\MessengerFaker\MessengerFaker;

class ReadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:read 
                                            {thread : ID of the thread you wish to mark read}
                                            {--admins : Only mark admins read if group thread}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark participants as read.';

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

        $faker->read();

        $this->info("Finished marking participants in {$faker->getThreadName()} read!");
    }
}
