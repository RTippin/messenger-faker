<?php

namespace RTippin\MessengerFaker\Commands;

use Symfony\Component\Console\Input\InputOption;
use Throwable;

class SystemCommand extends BaseFakerCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'messenger:faker:system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants send system messages.';

    /**
     * The default count option value for iterations.
     *
     * @var int
     */
    protected int $count = 1;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        if (! $this->setupFaker()) {
            return;
        }

        $this->outputThreadMessage('now sending system messages...');

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
            $this->outputExceptionMessage($e);

            return;
        }

        $this->finishProgressBar();

        $this->outputFinalMessage('system messages');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            ['type', null, InputOption::VALUE_OPTIONAL, 'Specify system message (INT) type. Random will be chosen if not specified'],
        ]);
    }
}
