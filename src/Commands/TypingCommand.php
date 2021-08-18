<?php

namespace RTippin\MessengerFaker\Commands;

use Throwable;

class TypingCommand extends BaseFakerCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'messenger:faker:typing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants type.';

    /**
     * Whether the command has a count / iterates.
     *
     * @var bool
     */
    protected bool $hasCount = false;

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

        $this->outputThreadMessage('now making participants type...');

        try {
            $this->faker->typing();
        } catch (Throwable $e) {
            $this->outputExceptionMessage($e);

            return;
        }

        $this->outputFinalMessage('typing');
    }
}
