<?php

namespace RTippin\MessengerFaker\Commands;

use Throwable;

class MessageCommand extends BaseFakerCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'messenger:faker:message';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants send messages. Will also emit typing and mark read.';

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

        $this->outputThreadMessage('now messaging...');

        $this->startProgressBar();

        try {
            for ($x = 1; $x <= $this->option('count'); $x++) {
                $this->faker->message($this->option('count') <= $x);
            }
        } catch (Throwable $e) {
            $this->outputExceptionMessage($e);

            return;
        }

        $this->finishProgressBar();

        $this->outputFinalMessage('messages');
    }
}
