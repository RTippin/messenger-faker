<?php

namespace RTippin\MessengerFaker\Commands;

use Illuminate\Support\Arr;
use Throwable;

class RandomCommand extends BaseFakerCommand
{
    /**
     * Faker commands we allow.
     */
    const FakerCommands = [
        'audio',
        'document',
        'image',
        'knock',
        'message',
        'react',
        'system',
        'typing',
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
    protected $description = 'Send random actions, cycling through our existing commands.';

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
                $this->callSilent('messenger:faker:'.Arr::random(self::FakerCommands, 1)[0], [
                    'thread' => $this->faker->getThread()->id,
                    '--admins' => $this->option('admins'),
                    '--delay' => $this->option('delay'),
                    '--silent' => $this->option('silent'),
                ]);

                $this->bar->advance();
            }
        } catch (Throwable $e) {
            $this->outputExceptionMessage($e);

            return;
        }

        $this->finishProgressBar(false);

        $this->outputFinalMessage('random actions');
    }
}
