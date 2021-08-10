<?php

namespace RTippin\MessengerFaker\Commands;

use Throwable;

class UnReadCommand extends BaseFakerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:unread 
                                            {thread? : ID of the thread you want to seed. Random if not set}
                                            {--admins : Only mark admins unread if group thread}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark participants as unread.';

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
            $this->faker->unread();
        } catch (Throwable $e) {
            $this->exceptionMessageOutput($e);

            return;
        }

        $this->outputFinalMessage('unread');
    }
}
