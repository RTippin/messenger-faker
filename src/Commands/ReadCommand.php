<?php

namespace RTippin\MessengerFaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RTippin\MessengerFaker\Faker\Read;

class ReadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:read {thread : ID of the thread you wish to mark read}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark all participants as read.';

    /**
     * Execute the console command.
     *
     * @param Read $read
     * @return void
     */
    public function handle(Read $read): void
    {
        try {
            $read->setThreadWithId($this->argument('thread'))->setLatestMessage();
        } catch (ModelNotFoundException $e) {
            $this->error('Thread not found.');

            return;
        }

        $read->execute();

        $this->info("Finished marking {$read->getThreadName()} read!");
    }
}
