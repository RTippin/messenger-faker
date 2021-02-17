<?php

namespace RTippin\MessengerFaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RTippin\MessengerFaker\Faker\UnRead;

class UnReadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:unread 
                                            {thread : ID of the thread you wish to mark participants unread}
                                            {--admins : Only mark admins unread if group thread}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark participants as unread.';

    /**
     * Execute the console command.
     *
     * @param UnRead $unread
     * @return void
     */
    public function handle(UnRead $unread): void
    {
        try {
            $unread->setThreadWithId($this->argument('thread'));
        } catch (ModelNotFoundException $e) {
            $this->error('Thread not found.');

            return;
        }

        if ($this->option('admins')) {
            $unread->useOnlyAdmins();
        }

        $unread->execute();

        $this->info("Finished marking {$unread->getThreadName()} participants as unread!");
    }
}
