<?php

namespace RTippin\MessengerFaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RTippin\MessengerFaker\MessengerFaker;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

abstract class BaseFakerCommand extends Command
{
    /**
     * @var MessengerFaker
     */
    protected MessengerFaker $faker;

    /**
     * @var ProgressBar
     */
    private ProgressBar $bar;

    /**
     * @param MessengerFaker $faker
     */
    public function __construct(MessengerFaker $faker)
    {
        parent::__construct();

        $this->faker = $faker;
    }

    /**
     * Set the thread on our faker instance when the command is loaded.
     *
     * @param int|null $loadMessageCount
     * @return bool
     */
    protected function initiateThread(?int $loadMessageCount = null): bool
    {
        try {
            $this->faker
                ->setThreadWithId(
                    $this->argument('thread') ?: null,
                    $this->hasOption('admins')
                        ? $this->option('admins')
                        : false
                )
                ->setDelay(
                    $this->hasOption('delay')
                        ? $this->option('delay')
                        : 0
                );

            if (! is_null($loadMessageCount)) {
                $this->faker->setMessages($loadMessageCount);
            }
        } catch (ModelNotFoundException $e) {
            $this->error('Thread not found.');

            return false;
        } catch (Throwable $e) {
            $this->exceptionMessageOutput($e);

            return false;
        }

        return true;
    }

    /**
     * Start the progress bar for this command.
     */
    protected function startProgressBar(): void
    {
        $this->bar = $this->output->createProgressBar($this->option('count'));

        $this->bar->start();
    }

    /**
     * Advance the progress bar.
     */
    protected function advanceProgressBar(): void
    {
        $this->bar->advance();
    }

    /**
     * Finish the progress bar.
     */
    protected function finishProgressBar(): void
    {
        $this->bar->finish();
    }

    /**
     * Out put the final message.
     *
     * @param string $message
     * @param int|null $count
     */
    protected function outputFinalMessage(string $message, ?int $count = null): void
    {
        $this->line('');
        $this->line('');
        $this->info('Finished sending'.(! is_null($count) ? ' '.$count : '')." $message to {$this->faker->getThreadName()}!");
        $this->line('');
    }

    /**
     * Output our exception message.
     *
     * @param Throwable $e
     */
    protected function exceptionMessageOutput(Throwable $e): void
    {
        $this->line('');
        $this->line('');
        $this->error($e->getMessage());
    }
}
