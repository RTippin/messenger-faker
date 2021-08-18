<?php

namespace RTippin\MessengerFaker\Commands;

use Symfony\Component\Console\Input\InputOption;
use Throwable;

class AudioCommand extends BaseFakerCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'messenger:faker:audio';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants send audio messages. Will also emit typing and mark read.';

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

        $this->outputThreadMessage('now messaging audio using '.($this->option('url') ?: 'a random audio file from '.config('messenger-faker.paths.audio')));

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
            $this->outputExceptionMessage($e);

            return;
        }

        $this->finishProgressBar();

        $this->outputFinalMessage('audio messages');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            ['url', null, InputOption::VALUE_OPTIONAL, 'Set the path/URL we grab a audio from. Default uses local storage'],
        ]);
    }
}
