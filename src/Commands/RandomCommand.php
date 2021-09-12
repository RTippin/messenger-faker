<?php

namespace RTippin\MessengerFaker\Commands;

use Illuminate\Support\Arr;
use Throwable;

class RandomCommand extends BaseFakerCommand
{
    /**
     * Faker commands we allow and if they have counts.
     */
    const FakerCommands = [
        'audio' => true,
        'document' => true,
        'image' => true,
        'knock' => false,
        'message' => true,
        'react' => true,
        'system' => true,
        'typing' => false,
    ];

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
    protected $description = 'Send random actions, cycling through our existing commands and using their default counts.';

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

        $this->outputThreadMessage('now sending random actions...');
        $this->newLine();

        $this->startProgressBar(false);

        try {
            for ($x = 1; $x <= $this->option('count'); $x++) {
                $command = $this->getRandomCommand();

                $this->callSilent('messenger:faker:'.$command, $this->getCommandOptions($command));

                $this->bar->advance();
            }
        } catch (Throwable $e) {
            $this->outputExceptionMessage($e);

            return;
        }

        $this->finishProgressBar(false);

        $this->outputFinalMessage('random actions');
    }

    /**
     * @return string
     */
    private function getRandomCommand(): string
    {
        return array_key_first(
            Arr::random(self::FakerCommands, 1, true)
        );
    }

    /**
     * @param  string  $command
     * @return array
     */
    private function getCommandOptions(string $command): array
    {
        $options = [
            'thread' => $this->faker->getThread()->id,
            '--admins' => $this->option('admins'),
            '--silent' => $this->option('silent'),
        ];

        if (self::FakerCommands[$command] === true) {
            $options['--delay'] = $this->option('delay');
        }

        return $options;
    }
}
