<?php

namespace RTippin\MessengerFaker\Commands;

use Throwable;

class SystemCommand extends BaseFakerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:system 
                                            {thread? : ID of the thread you want to seed. Random if not set}
                                            {--type= : Specify system message (INT) type. Random will be chosen if not specified}
                                            {--count=1 : Number of system messages to send}
                                            {--delay=3 : Delay between each system message being sent}
                                            {--admins : Only use admins to send system messages if group thread}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants send system messages.';

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
        $this->info("Found {$this->faker->getThreadName()}, now sending system messages...");
        $this->line('');

        $this->startProgressBar();

        try {
            for ($x = 1; $x <= $this->option('count'); $x++) {
                $this->faker->system(
                    $this->option('type'),
                    $this->option('count') <= $x
                );

                $this->advanceProgressBar();
            }
        } catch (Throwable $e) {
            $this->exceptionMessageOutput($e);

            return;
        }

        $this->finishProgressBar();

        $this->outputFinalMessage('system messages', $this->option('count'));
    }
}
