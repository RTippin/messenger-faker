<?php

namespace RTippin\MessengerFaker\Commands;

use Throwable;

class AudioCommand extends BaseFakerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:audio 
                                            {thread? : ID of the thread you want to seed. Random if not set}
                                            {--count=1 : Number of audio messages to send}
                                            {--delay=3 : Delay between each audio message being sent}
                                            {--admins : Only use admins to send audio messages if group thread}
                                            {--url= : Set the path/URL we grab a audio from. Default uses local storage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants send audio messages. Will also emit typing and mark read.';

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
        $this->info("Found {$this->faker->getThreadName()}, now messaging audio...");
        $this->info('Using '.($this->option('url') ?? 'a random audio file from '.config('messenger-faker.paths.audio')));
        $this->line('');

        $this->startProgressBar();

        try {
            for ($x = 1; $x <= $this->option('count'); $x++) {
                $this->faker->audio(
                    $this->option('count') <= $x,
                    $this->option('url')
                );

                $this->advanceProgressBar();
            }
        } catch (Throwable $e) {
            $this->exceptionMessageOutput($e);

            return;
        }

        $this->finishProgressBar();

        $this->outputFinalMessage('audio messages', $this->option('count'));
    }
}
