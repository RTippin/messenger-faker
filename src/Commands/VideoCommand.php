<?php

namespace RTippin\MessengerFaker\Commands;

use Symfony\Component\Console\Input\InputOption;
use Throwable;

class VideoCommand extends BaseFakerCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'messenger:faker:video';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants send video messages. Will also emit typing and mark read.';

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

        $this->outputThreadMessage('now messaging videos using '.($this->option('url') ?: 'a random video file from '.config('messenger-faker.paths.videos')));

        $this->startProgressBar();

        try {
            for ($x = 1; $x <= $this->option('count'); $x++) {
                $this->faker->video(
                    $this->option('count') <= $x,
                    $this->option('url')
                );
            }
        } catch (Throwable $e) {
            $this->outputExceptionMessage($e);

            return;
        }

        $this->finishProgressBar();

        $this->outputFinalMessage('video messages');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            ['url', null, InputOption::VALUE_OPTIONAL, 'Set the path/URL we grab a videos from. Default uses local storage'],
        ]);
    }
}
