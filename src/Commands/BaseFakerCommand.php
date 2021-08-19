<?php

namespace RTippin\MessengerFaker\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RTippin\MessengerFaker\MessengerFaker;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

abstract class BaseFakerCommand extends Command
{
    /**
     * The default delay option value.
     *
     * @var int
     */
    protected int $delay = 2;

    /**
     * Whether the command has a count / iterates.
     *
     * @var bool
     */
    protected bool $hasCount = true;

    /**
     * The default count option value for iterations.
     *
     * @var int
     */
    protected int $count = 5;

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
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['thread', InputArgument::OPTIONAL, 'ID of the thread you want to use. Random if not set'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $options = [
            ['admins', null, InputOption::VALUE_NONE, 'Only use admins from the given thread, if any'],
            ['delay', null, InputOption::VALUE_REQUIRED, 'Delay between each iteration', $this->delay],
            ['silent', null, InputOption::VALUE_NONE, 'Silences all broadcast and events'],
        ];

        if ($this->hasCount) {
            $options[] = ['count', null, InputOption::VALUE_REQUIRED, 'Number of iterations we will run', $this->count];
        }

        return $options;
    }

    /**
     * Set the thread on our faker instance when the command is loaded.
     *
     * @param int|null $loadMessageCount
     * @return bool
     */
    protected function setupFaker(?int $loadMessageCount = null): bool
    {
        try {
            $this->faker
                ->setThreadWithId(
                    $this->argument('thread') ?: null,
                    $this->option('admins')
                )
                ->setDelay($this->option('delay'))
                ->setSilent($this->option('silent'));

            if (! is_null($loadMessageCount)) {
                $this->faker->setMessages($loadMessageCount);
            }

            return true;
        } catch (ModelNotFoundException $e) {
            $this->error('Thread not found.');
        } catch (Throwable $e) {
            $this->outputExceptionMessage($e);
        }

        return false;
    }

    /**
     * Output the thread found action message.
     *
     * @param string $message
     */
    protected function outputThreadMessage(string $message): void
    {
        $this->newLine();
        $this->info("Found {$this->faker->getThreadName()}, ".$message);
    }

    /**
     * Start the progress bar for this command.
     */
    protected function startProgressBar(): void
    {
        $this->bar = $this->output->createProgressBar($this->option('count'));

        $this->faker->setProgressBar($this->bar);

        $this->newLine();
        $this->bar->start();
    }

    /**
     * Finish the progress bar.
     */
    protected function finishProgressBar(): void
    {
        $this->faker->setProgressBar(null);

        $this->bar->finish();
        $this->newLine(2);
    }

    /**
     * Out put the final message.
     *
     * @param string $message
     */
    protected function outputFinalMessage(string $message): void
    {
        $this->info('Finished sending'.($this->hasOption('count') ? ' '.$this->option('count') : '')." $message to {$this->faker->getThreadName()}!");
        $this->newLine();
    }

    /**
     * Output our exception message.
     *
     * @param Throwable $e
     */
    protected function outputExceptionMessage(Throwable $e): void
    {
        $this->newLine(2);
        $this->error($e->getMessage());
    }
}
