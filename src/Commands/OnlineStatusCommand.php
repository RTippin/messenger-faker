<?php

namespace RTippin\MessengerFaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RTippin\MessengerFaker\MessengerFaker;

class OnlineStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:status 
                                            {thread : ID of the thread you wish to have online status}
                                            {--status=online : Online status to set participants (online/away/offline)}
                                            {--admins : Only use admins for online status if group thread}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set participants online status.';

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

        $faker->status($this->option('status'));

        $this->info("Finished making participants in {$faker->getThreadName()} to {$this->option('status')}!");
    }
}
