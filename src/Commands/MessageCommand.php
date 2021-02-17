<?php

namespace RTippin\MessengerFaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\MessengerFaker\Faker\Message;
use Throwable;

class MessageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:message 
                                            {thread : ID of the thread you wish to have messaged}
                                            {count=5 : Number of messages to send}
                                            {--delay=3 : Delay between each message being sent}
                                            {--admins : Only use admins for messaging}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants send messages. Will also emit typing and mark read.';

    /**
     * Execute the console command.
     *
     * @param Message $message
     * @return void
     * @throws InvalidProviderException
     * @throws Throwable
     */
    public function handle(Message $message): void
    {
        try {
            $message->setThreadWithId($this->argument('thread'))->setup(
                $this->option('delay'),
                $this->option('admins')
            );
        } catch (ModelNotFoundException $e) {
            $this->error('Thread not found.');

            return;
        }

        $this->info("Found {$message->getThreadName()}, now messaging...");

        $bar = $this->output->createProgressBar($this->argument('count'));

        $bar->start();

        for ($x = 1; $x <= $this->argument('count'); $x++) {
            $message->execute($this->argument('count') <= $x);

            $bar->advance();
        }

        $bar->finish();

        $this->line(' (done)');

        $this->info("Finished sending {$this->argument('count')} messages to {$message->getThreadName()}!");
    }
}
