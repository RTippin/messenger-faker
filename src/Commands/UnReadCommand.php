<?php

namespace RTippin\MessengerFaker\Commands;

use Throwable;

class UnReadCommand extends BaseFakerCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'messenger:faker:unread';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark participants as unread.';

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

        $this->outputThreadMessage('now marking participants as unread...');

        try {
            $this->faker->unread();
        } catch (Throwable $e) {
            $this->outputExceptionMessage($e);

            return;
        }

        $this->outputFinalMessage('unread');
    }
}
