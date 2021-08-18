<?php

namespace RTippin\MessengerFaker\Commands;

use Symfony\Component\Console\Input\InputOption;
use Throwable;

class ReactCommand extends BaseFakerCommand
{
    /**
     * The default delay option value.
     *
     * @var int
     */
    protected int $delay = 1;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'messenger:faker:react';

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
        if (! $this->setupFaker($this->option('messages'))) {
            return;
        }

        $this->outputThreadMessage("now adding reactions to the {$this->option('messages')} most recent messages...");

        $this->startProgressBar();

        try {
            for ($x = 1; $x <= $this->option('count'); $x++) {
                $this->faker->reaction($this->option('count') <= $x);

                $this->advanceProgressBar();
            }
        } catch (Throwable $e) {
            $this->outputExceptionMessage($e);

            return;
        }

        $this->finishProgressBar();

        $this->outputFinalMessage('reactions');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            ['count', null, InputOption::VALUE_REQUIRED, 'Number of reactions to send', 5],
            ['messages', null, InputOption::VALUE_REQUIRED, 'Number of latest messages to choose from for reacting', 5],
        ]);
    }
}
