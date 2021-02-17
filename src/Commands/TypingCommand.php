<?php

namespace RTippin\MessengerFaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RTippin\MessengerFaker\Faker\Typing;

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
     * @param Typing $typing
     * @return void
     */
    public function handle(Typing $typing): void
    {
        try {
            $typing->setThreadWithId($this->argument('thread'));
        } catch (ModelNotFoundException $e) {
            $this->error('Thread not found.');

            return;
        }
        if ($this->option('admins')) {
            $typing->useOnlyAdmins();
        }

        $typing->execute();

        $this->info("Finished making participants in {$typing->getThreadName()} type!");
    }
}
