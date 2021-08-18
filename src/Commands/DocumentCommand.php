<?php

namespace RTippin\MessengerFaker\Commands;

use Symfony\Component\Console\Input\InputOption;
use Throwable;

class DocumentCommand extends BaseFakerCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'messenger:faker:document';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants send document messages. Will also emit typing and mark read.';

    /**
     * The default count option value for iterations.
     *
     * @var int
     */
    protected int $count = 1;

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

        $this->outputThreadMessage('now messaging documents using '.($this->option('url') ?: 'a random document from '.config('messenger-faker.paths.documents')));

        $this->startProgressBar();

        try {
            for ($x = 1; $x <= $this->option('count'); $x++) {
                $this->faker->document(
                    $this->option('count') <= $x,
                    $this->option('url')
                );

                $this->advanceProgressBar();
            }
        } catch (Throwable $e) {
            $this->outputExceptionMessage($e);

            return;
        }

        $this->finishProgressBar();

        $this->outputFinalMessage('document messages');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            ['url', null, InputOption::VALUE_OPTIONAL, 'Set the path/URL we grab a document from. Default uses local storage'],
        ]);
    }
}
