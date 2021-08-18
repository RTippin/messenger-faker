<?php

namespace RTippin\MessengerFaker\Commands;

use Symfony\Component\Console\Input\InputOption;
use Throwable;

class ImageCommand extends BaseFakerCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'messenger:faker:image';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants send image messages. Will also emit typing and mark read.';

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

        $message = $this->option('url') ?: config('messenger-faker.default_image_url');

        if ($this->option('local')) {
            $message = 'a random image from '.config('messenger-faker.paths.images');
        }

        $this->outputThreadMessage("now messaging images using $message");

        $this->startProgressBar();

        try {
            for ($x = 1; $x <= $this->option('count'); $x++) {
                $this->faker->image(
                    $this->option('count') <= $x,
                    $this->option('local'),
                    $this->option('url')
                );
            }
        } catch (Throwable $e) {
            $this->outputExceptionMessage($e);

            return;
        }

        $this->finishProgressBar();

        $this->outputFinalMessage('image messages');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            ['local', null, InputOption::VALUE_NONE, 'Pick a random image stored locally under storage/faker/images/'],
            ['url', null, InputOption::VALUE_OPTIONAL, 'Set the path/URL we grab an image from. Default uses unsplash'],
        ]);
    }
}
