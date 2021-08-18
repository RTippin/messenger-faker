<?php

namespace RTippin\MessengerFaker\Commands;

use Throwable;

class ReadCommand extends BaseFakerCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'messenger:faker:read';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark participants as read.';

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

        $this->outputThreadMessage('now marking participants as read');

        try {
            $this->faker->read();
        } catch (Throwable $e) {
            $this->outputExceptionMessage($e);

            return;
        }

        $this->outputFinalMessage('mark participants read');
    }
}
