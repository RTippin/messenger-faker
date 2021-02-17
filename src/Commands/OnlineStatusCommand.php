<?php

namespace RTippin\MessengerFaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RTippin\MessengerFaker\Faker\OnlineStatus;

class OnlineStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:status 
                                            {thread : ID of the thread you wish to have online status}
                                            {status=online : Online status to set participants (online/away/offline)}
                                            {--admins : Only use admins for online status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set participants online status.';

    /**
     * Execute the console command.
     *
     * @param OnlineStatus $status
     * @return void
     */
    public function handle(OnlineStatus $status): void
    {
        try {
            $status->setThreadWithId($this->argument('thread'));
        } catch (ModelNotFoundException $e) {
            $this->error('Thread not found.');

            return;
        }

        if ($this->option('admins')) {
            $status->useOnlyAdmins();
        }

        $status->execute($this->argument('status'));

        $this->info("Finished making participants in {$status->getThreadName()} to {$this->argument('status')}!");
    }
}
