<?php

namespace RTippin\MessengerFaker\Commands;

use Symfony\Component\Console\Input\InputOption;
use Throwable;

class RandomCommand extends BaseFakerCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'messenger:faker:random';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Random';

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

        $this->outputThreadMessage('now sending random actions'.($this->option('no-files') ? ' without files...' : '...'));
        $this->newLine();

        try {
            for ($x = 1; $x <= $this->option('count'); $x++) {
                $this->faker->random(
                    $this->option('count') <= $x,
                    $this->option('no-files'),
                    $this
                );
            }
        } catch (Throwable $e) {
            $this->outputExceptionMessage($e);

            return;
        }

        $this->newLine();
        $this->outputFinalMessage('random actions');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            ['no-files', null, InputOption::VALUE_NONE, 'Disables using images, documents, and audio files'],
        ]);
    }
}
