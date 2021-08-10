<?php

namespace RTippin\MessengerFaker\Commands;

use Throwable;

class MessageCommand extends BaseFakerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:message 
                                            {thread? : ID of the thread you want to seed. Random if not set}
                                            {--count=5 : Number of messages to send}
                                            {--delay=3 : Delay between each message being sent}
                                            {--admins : Only use admins to send messages if group thread}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants send messages. Will also emit typing and mark read.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        if (! $this->initiateThread()) {
            return;
        }

        $this->line('');
        $this->info("Found {$this->faker->getThreadName()}, now messaging...");
        $this->line('');

        $this->startProgressBar();

        try {
            for ($x = 1; $x <= $this->option('count'); $x++) {
                $this->faker->message($this->option('count') <= $x);

                $this->advanceProgressBar();
            }
        } catch (Throwable $e) {
            $this->exceptionMessageOutput($e);

            return;
        }

        $this->finishProgressBar();

        $this->outputFinalMessage('messages', $this->option('count'));
    }
}
