<?php

namespace RTippin\MessengerFaker\Commands;

use Throwable;

class ImageCommand extends BaseFakerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:image 
                                            {thread? : ID of the thread you want to seed. Random if not set}
                                            {--count=1 : Number of image messages to send}
                                            {--delay=3 : Delay between each image message being sent}
                                            {--admins : Only use admins to send image messages if group thread}
                                            {--local : Pick a random image stored locally under storage/faker/images/}
                                            {--url= : Set the path/URL we grab an image from. Default uses unsplash}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants send image messages. Will also emit typing and mark read.';

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

        if ($this->option('local')) {
            $message = 'a random image from '.config('messenger-faker.paths.images');
        } else {
            $message = $this->option('url') ?? config('messenger-faker.default_image_url');
        }

        $this->line('');
        $this->info("Found {$this->faker->getThreadName()}, now messaging images...");
        $this->info("Using $message");
        $this->line('');

        $this->startProgressBar();

        try {
            for ($x = 1; $x <= $this->option('count'); $x++) {
                $this->faker->image(
                    $this->option('count') <= $x,
                    $this->option('local'),
                    $this->option('url')
                );

                $this->advanceProgressBar();
            }
        } catch (Throwable $e) {
            $this->exceptionMessageOutput($e);

            return;
        }

        $this->finishProgressBar();

        $this->outputFinalMessage('image messages', $this->option('count'));
    }
}
