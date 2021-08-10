<?php

namespace RTippin\MessengerFaker\Commands;

use Throwable;

class TypingCommand extends BaseFakerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:typing 
                                            {thread? : ID of the thread you want to seed. Random if not set}
                                            {--admins : Only use admins for typing if group thread}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants type.';

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
            $this->faker->typing();
        } catch (Throwable $e) {
            $this->exceptionMessageOutput($e);

            return;
        }

        $this->outputFinalMessage('typing');
    }
}
