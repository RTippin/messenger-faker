<?php

namespace RTippin\MessengerFaker\Commands;

use Throwable;

class ReactCommand extends BaseFakerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:react 
                                            {thread? : ID of the thread you want to seed. Random if not set}
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
     * @return void
     */
    public function handle(): void
    {
        if (! $this->initiateThread($this->option('messages'))) {
            return;
        }

        $this->line('');
        $this->info("Found {$this->faker->getThreadName()}, now adding reactions to the {$this->option('messages')} most recent messages...");
        $this->line('');

        $this->startProgressBar();

        try {
            for ($x = 1; $x <= $this->option('count'); $x++) {
                $this->faker->reaction($this->option('count') <= $x);

                $this->advanceProgressBar();
            }
        } catch (Throwable $e) {
            $this->exceptionMessageOutput($e);

            return;
        }

        $this->finishProgressBar();

        $this->outputFinalMessage('reactions', $this->option('count'));
    }
}
