<?php

namespace RTippin\MessengerFaker\Commands;

use Throwable;

class KnockCommand extends BaseFakerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:knock {thread? : ID of the thread you want to seed. Random if not set}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a knock to the given thread.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        if (! $this->initiateThread()) {
            return;
        }

        try {
            $this->faker->knock();
        } catch (Throwable $e) {
            $this->exceptionMessageOutput($e);

            return;
        }

        $this->outputFinalMessage('knocks');
    }
}
