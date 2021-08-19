<?php

namespace RTippin\MessengerFaker\Commands;

use Throwable;

class KnockCommand extends BaseFakerCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'messenger:faker:knock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a knock to the given thread.';

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

        $this->outputThreadMessage('now knocking...');

        try {
            $this->faker->knock();
        } catch (Throwable $e) {
            $this->outputExceptionMessage($e);

            return;
        }

        $this->outputFinalMessage('knocks');
    }
}
